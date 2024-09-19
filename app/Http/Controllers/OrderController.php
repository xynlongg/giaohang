<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Product;
use App\Models\PostOffice;
use App\Models\OrderStatusLog;
use App\Models\OrderLocationHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode; 
use Illuminate\Support\Str;
use App\Events\OrderUpdated;
use App\Models\UserAddress;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OrdersImport;
use App\Exports\OrderTemplateExport;
use App\Models\ProductCategory;
use App\Models\WarrantyPackage;
    class OrderController extends Controller
    {
        protected $orderAssignmentService;

        public function __construct(OrderAssignmentService $orderAssignmentService)
        {
            $this->orderAssignmentService = $orderAssignmentService;
        }
        public function index(Request $request)
        {
            $query = Order::query();

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('sender_name', 'like', '%' . $request->search . '%')
                        ->orWhere('receiver_name', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('date') && $request->date != '') {
                $query->whereDate('created_at', $request->date);
            }

            $orders = $query->paginate(10);

            return view('orders.index', compact('orders'));
        }

        public function create()
        {
            $products = Product::all();
            $postOffices = PostOffice::all();
            $userAddresses = UserAddress::where('user_id', Auth::id())->get();
            $productCategories = ProductCategory::with('warrantyPackages')->get();
        
            return view('orders.create', compact('products', 'postOffices', 'userAddresses', 'productCategories'));
        }
        public function store(Request $request)
        {
            Log::info('Attempting to create a new order', ['request' => $request->all()]);
    
            try {
                DB::beginTransaction();
    
                $validationRules = [
                    'sender_address_select' => 'required',
                    'sender_name' => 'required_if:sender_address_select,new|string|max:255',
                    'sender_phone' => 'required_if:sender_address_select,new|string|max:20',
                    'sender_address' => 'required_if:sender_address_select,new|string|max:255',
                    'sender_coordinates' => 'required_if:sender_address_select,new|json',
                    'receiver_name' => 'required|string|max:255',
                    'receiver_phone' => 'required|string|max:20',
                    'receiver_address' => 'required|string|max:255',
                    'receiver_coordinates' => 'required|json',
                    'is_pickup_at_post_office' => 'required|boolean',
                    'pickup_location_id' => 'required_if:is_pickup_at_post_office,1|exists:post_offices,id',
                    'pickup_date' => 'required_if:is_pickup_at_post_office,0|date|after:today',
                    'pickup_time' => 'required_if:is_pickup_at_post_office,0|date_format:H:i',
                    'delivery_date' => 'required|date|after:pickup_date',
                    'products' => 'required|array',
                    'products.*.id' => 'required|exists:products,id',
                    'products.*.quantity' => 'required|integer|min:1',
                    'products.*.cod_amount' => 'required|numeric|min:0',
                    'products.*.weight' => 'required|numeric|min:0',
                    'category_id' => 'required|exists:product_categories,id',
                    'warranty_package_id' => 'required|exists:warranty_packages,id',
                ];
    
                $validatedData = $request->validate($validationRules);
    
                Log::info('Validation passed', ['validatedData' => $validatedData]);
    
                // Process sender information
                if ($validatedData['sender_address_select'] !== 'new') {
                    $savedAddress = UserAddress::findOrFail($validatedData['sender_address_select']);
                    $validatedData['sender_name'] = $savedAddress->name;
                    $validatedData['sender_phone'] = $savedAddress->phone;
                    $validatedData['sender_address'] = $savedAddress->address;
                    $validatedData['sender_coordinates'] = json_encode($savedAddress->coordinates);
                } elseif ($request->has('save_sender_address') && $request->save_sender_address) {
                    UserAddress::create([
                        'user_id' => Auth::id(),
                        'name' => $validatedData['sender_name'],
                        'phone' => $validatedData['sender_phone'],
                        'address' => $validatedData['sender_address'],
                        'coordinates' => json_decode($validatedData['sender_coordinates'], true),
                    ]);
                }
    
                $senderCoordinates = json_decode($validatedData['sender_coordinates'], true);
                $receiverCoordinates = json_decode($validatedData['receiver_coordinates'], true);
    
                // Calculate shipping fee
                $distance = $this->calculateDistance(
                    $senderCoordinates[1], $senderCoordinates[0],
                    $receiverCoordinates[1], $receiverCoordinates[0]
                );
                $shippingFee = $this->calculateShippingFee($distance);
    
                Log::info('Calculated shipping fee', ['distance' => $distance, 'shippingFee' => $shippingFee]);
    
                // Get warranty package
                $warrantyPackage = WarrantyPackage::findOrFail($validatedData['warranty_package_id']);
    
                // Calculate total amounts
                $totalWeight = 0;
                $totalCod = 0;
                $totalValue = 0;
    
                foreach ($validatedData['products'] as $productData) {
                    $product = Product::findOrFail($productData['id']);
                    $totalWeight += $productData['weight'] * $productData['quantity'];
                    $totalCod += $productData['cod_amount'] * $productData['quantity'];
                    $totalValue += $product->value * $productData['quantity'];
                }
    
                $totalAmount = $totalCod + $shippingFee + $warrantyPackage->price;
    
                Log::info('Calculated totals', [
                    'totalWeight' => $totalWeight,
                    'totalCod' => $totalCod,
                    'totalValue' => $totalValue,
                    'totalAmount' => $totalAmount
                ]);
    
                // Generate tracking number
                $trackingNumber = $this->generateTrackingNumber($validatedData['receiver_address']);
                Log::info('Generated tracking number', ['trackingNumber' => $trackingNumber]);
    
                // Create order
                $order = new Order([
                    'is_pickup_at_post_office' => $validatedData['is_pickup_at_post_office'],
                    'pickup_location_id' => $validatedData['pickup_location_id'] ?? null,
                    'pickup_date' => $validatedData['pickup_date'] ?? null,
                    'pickup_time' => $validatedData['pickup_time'] ?? null,
                    'sender_name' => $validatedData['sender_name'],
                    'sender_phone' => $validatedData['sender_phone'],
                    'sender_address' => $validatedData['sender_address'],
                    'sender_coordinates' => $senderCoordinates,
                    'receiver_name' => $validatedData['receiver_name'],
                    'receiver_phone' => $validatedData['receiver_phone'],
                    'receiver_address' => $validatedData['receiver_address'],
                    'receiver_coordinates' => $receiverCoordinates,
                    'total_weight' => $totalWeight,
                    'total_cod' => $totalCod,
                    'total_value' => $totalValue,
                    'shipping_fee' => $shippingFee,
                    'total_amount' => $totalAmount,
                    'delivery_date' => $validatedData['delivery_date'],
                    'status' => 'pending',
                    'category_id' => $validatedData['category_id'],
                    'warranty_package_id' => $validatedData['warranty_package_id'],
                    'warranty_fee' => $warrantyPackage->price,
                    'tracking_number' => $trackingNumber,
                    'current_location_id' => null,
                    'current_location_type' => 'sender',
                    'current_coordinates' => $senderCoordinates,
                    'current_location' => $validatedData['sender_address'],
                ]);
    
                if (!$order->save()) {
                    throw new \Exception('Failed to save order');
                }
    
                Log::info('Order created', ['orderId' => $order->id, 'orderData' => $order->toArray()]);
    
                // Attach products to order
                foreach ($validatedData['products'] as $productData) {
                    $product = Product::findOrFail($productData['id']);
                    $order->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'cod_amount' => $productData['cod_amount'],
                        'weight' => $productData['weight'],
                    ]);
                    Log::info('Attached product to order', [
                        'orderId' => $order->id,
                        'productId' => $product->id,
                        'quantity' => $productData['quantity'],
                        'codAmount' => $productData['cod_amount'],
                        'weight' => $productData['weight']
                    ]);
                }
    
                // Generate QR code
                $qrCode = QrCode::size(300)->generate($trackingNumber);
    
                $order->update([
                    'qr_code' => $qrCode,
                ]);
    
                // Create initial order location history
                OrderLocationHistory::create([
                    'order_id' => $order->id,
                    'location_type' => 'sender',
                    'post_office_id' => null,
                    'address' => $validatedData['sender_address'],
                    'coordinates' => $senderCoordinates,
                    'status' => 'pending',
                    'timestamp' => now(),
                ]);
    
                DB::commit();
    
                Log::info('Order creation completed successfully', [
                    'orderId' => $order->id,
                    'trackingNumber' => $trackingNumber,
                    'totalAmount' => $totalAmount,
                    'currentLocation' => $validatedData['sender_address']
                ]);
    
                return $this->sendSuccessResponse($request, 'Đơn hàng đã được tạo thành công.', [
                    'order_id' => $order->id,
                    'tracking_number' => $trackingNumber,
                    'qr_code' => $qrCode,
                ]);
    
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error creating order', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->sendErrorResponse($request, 'Đã xảy ra lỗi khi tạo đơn hàng: ' . $e->getMessage(), [], 500);
            }
        }
    
        private function calculateDistance($lat1, $lon1, $lat2, $lon2)
        {
            $R = 6371; // Radius of the earth in km
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = 
                sin($dLat/2) * sin($dLat/2) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
                sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = $R * $c; // Distance in km
            return $distance;
        }
    
        private function calculateShippingFee($distance)
        {
            if ($distance <= 5) {
                return 10000; // 10k VND cho dưới 5km
            } elseif ($distance <= 10) {
                return 15000; // 15k VND cho 5-10km
            } elseif ($distance <= 30) {
                return ceil($distance) * 1500; // 1500 VND * số km cho 10-30km
            } elseif ($distance <= 60) {
                return ceil($distance) * 1300; // 1300 VND * số km cho 31-60km
            } elseif ($distance <= 100) {
                return ceil($distance) * 1000; // 1000 VND * số km cho 61-100km
            } elseif ($distance <= 150) {
                return ceil($distance) * 700; // 700 VND * số km cho 100-150km
            } elseif ($distance <= 300) {
                return ceil($distance) * 600; // 600 VND * số km cho 150-300km
            } else {
                return ceil($distance) * 450; // 450 VND * số km cho trên 300km
            }
        }
        
        
        private function generateTrackingNumber($receiverAddress)
        {
            $provinceCode = $this->getProvinceCode($receiverAddress);
            do {
                $randomPart = strtoupper(Str::random(6));
                $number = $provinceCode . '_' . $randomPart;
            } while (Order::where('tracking_number', $number)->exists());

            return $number;
        }

        private function getProvinceCode($address)
        {
            $provinces = [
                'An Giang' => 'AG', 'Bà Rịa - Vũng Tàu' => 'BV', 'Bắc Giang' => 'BG', 'Bắc Kạn' => 'BK', 'Bạc Liêu' => 'BL',
                'Bắc Ninh' => 'BN', 'Bến Tre' => 'BT', 'Bình Định' => 'BD', 'Bình Dương' => 'BI', 'Bình Phước' => 'BP',
                'Bình Thuận' => 'BH', 'Cà Mau' => 'CM', 'Cần Thơ' => 'CT', 'Cao Bằng' => 'CB', 'Đà Nẵng' => 'DN',
                'Đắk Lắk' => 'DL', 'Đắk Nông' => 'DO', 'Điện Biên' => 'DB', 'Đồng Nai' => 'DA', 'Đồng Tháp' => 'DT',
                'Gia Lai' => 'GL', 'Hà Giang' => 'HG', 'Hà Nam' => 'HM', 'Hà Nội' => 'HN', 'Hà Tĩnh' => 'HT',
                'Hải Dương' => 'HD', 'Hải Phòng' => 'HP', 'Hậu Giang' => 'HU', 'Hòa Bình' => 'HB', 'Hưng Yên' => 'HY',
                'Khánh Hòa' => 'KH', 'Kiên Giang' => 'KG', 'Kon Tum' => 'KT', 'Lai Châu' => 'LC', 'Lâm Đồng' => 'LD',
                'Lạng Sơn' => 'LS', 'Lào Cai' => 'LO', 'Long An' => 'LA', 'Nam Định' => 'ND', 'Nghệ An' => 'NA',
                'Ninh Bình' => 'NB', 'Ninh Thuận' => 'NT', 'Phú Thọ' => 'PT', 'Phú Yên' => 'PY', 'Quảng Bình' => 'QB',
                'Quảng Nam' => 'QN', 'Quảng Ngãi' => 'QG', 'Quảng Ninh' => 'QI', 'Quảng Trị' => 'QT', 'Sóc Trăng' => 'ST',
                'Sơn La' => 'SL', 'Tây Ninh' => 'TN', 'Thái Bình' => 'TB', 'Thái Nguyên' => 'TY', 'Thanh Hóa' => 'TH',
                'Thừa Thiên Huế' => 'TT', 'Tiền Giang' => 'TG', 'Thành Phố Hồ Chí Minh' => 'HCM', 'Trà Vinh' => 'TV', 'Tuyên Quang' => 'TQ',
                'Vĩnh Long' => 'VL', 'Vĩnh Phúc' => 'VP', 'Yên Bái' => 'YB'
            ];

            foreach ($provinces as $province => $code) {
                if (stripos($address, $province) !== false) {
                    return $code;
                }
            }

            // Nếu không tìm thấy tỉnh thành phù hợp, trả về mã mặc định
            return 'XX';
        }
        private function sendErrorResponse(Request $request, $message, $errors = [], $statusCode = 400)
        {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $errors,
                ], $statusCode);
            }
            return redirect()->back()->withErrors($errors)->withInput()->with('error', $message);
        }
    
        // Thêm phương thức sendSuccessResponse (nếu chưa có)
        private function sendSuccessResponse(Request $request, $message, $data = [])
        {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $data,
                ]);
            }
            return redirect()->route('orders.index')->with('success', $message);
        }
        public function show(Order $order)
        {
            $postOffices = PostOffice::all();
            return view('orders.show', compact('order', 'postOffices'));
        }
        
        private function checkSameProvince($address1, $address2)
        {
            $provinces = [
                'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu',
                'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước',
                'Bình Thuận', 'Cà Mau', 'Cần Thơ', 'Cao Bằng', 'Đà Nẵng',
                'Đắk Lắk', 'Đắk Nông', 'Điện Biên', 'Đồng Nai', 'Đồng Tháp',
                'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Nội', 'Hà Tĩnh',
                'Hải Dương', 'Hải Phòng', 'Hậu Giang', 'Hòa Bình', 'Hưng Yên',
                'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lâm Đồng',
                'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định', 'Nghệ An',
                'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình',
                'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sóc Trăng',
                'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa',
                'Thừa Thiên Huế', 'Tiền Giang', 'TP Hồ Chí Minh', 'Trà Vinh', 'Tuyên Quang',
                'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'
            ];

            $getProvince = function ($address) use ($provinces) {
                $parts = explode(',', $address);
                $lastPart = trim(end($parts));
                foreach ($provinces as $province) {
                    if (stripos($lastPart, $province) !== false) {
                        return $province;
                    }
                }
                return null;
            };

            $province1 = $getProvince($address1);
            $province2 = $getProvince($address2);

            return $province1 && $province2 && $province1 === $province2;
        }

        public function searchByQR(Request $request)
        {
            $trackingNumber = $request->input('tracking_number');
            $order = Order::where('tracking_number', $trackingNumber)->first();
        
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng.'
                ], 404);
            }
        
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        }
        
    
        

        public function showSearchForm()
        {
            return view('orders.search_order_form');
        }

        public function searchOrder(Request $request)
        {
            $query = $request->input('query');
        
            $order = Order::where('tracking_number', 'like', "%$query%")
                        ->first();
        
            if (!$order) {
                return view('orders.search_order_form')->with('message', 'Không tìm thấy đơn hàng nào phù hợp.');
            }
        
            return view('orders.search_order', compact('order'));
        }

        public function destroy($id)
        {
            try {
                $order = Order::findOrFail($id);

                // Optionally, you can add additional logic here, like checking if the user is authorized to delete this order

                $order->delete();

                return redirect()->route('orders.index')->with('success', 'Đơn hàng đã được xóa thành công.');

            } catch (\Exception $e) {
                return redirect()->route('orders.index')->with('error', 'Đã xảy ra lỗi khi xóa đơn hàng: ' . $e->getMessage());
            }
        }

        public function showUpdateForm(Order $order)
        {
            $postOffices = PostOffice::all();
            return view('orders.update_status', compact('order', 'postOffices'));
        }

        

        private function getStatusClass($status)
        {
            $statusClasses = [
                'pending' => 'secondary',
                'confirmed' => 'primary',
                'picking_up' => 'info',
                'at_post_office' => 'warning',
                'delivering' => 'info',
                'delivered' => 'success',
            ];

            return $statusClasses[$status] ?? 'secondary';
        }

        public function confirmArrival(Request $request, Order $order)
        {
            $request->validate([
                'post_office_id' => 'required|exists:post_offices,id',
            ]);

            $postOffice = PostOffice::findOrFail($request->post_office_id);

            $order->update([
                'current_location_id' => $postOffice->id,
                'current_location_type' => 'post_office',
                'current_coordinates' => $postOffice->coordinates,
                'current_location' => $postOffice->address,
                'status' => 'arrived_at_post_office',
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 'arrived_at_post_office',
                'description' => "Đơn hàng đã đến bưu cục " . $postOffice->name,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Đã xác nhận đơn hàng đến bưu cục',
                'location' => $postOffice->name,
                'address' => $postOffice->address,
                'coordinates' => $postOffice->coordinates,
            ]); 
        }

        public function updateLocation(Request $request, Order $order)
        {
            $request->validate([
                'post_office_id' => 'required|exists:post_offices,id',
            ]);

            $postOffice = PostOffice::findOrFail($request->post_office_id);

            $order->update([
                'current_location_id' => $postOffice->id,
                'current_location_type' => 'post_office',
                'current_coordinates' => $postOffice->coordinates,
                'current_location' => $postOffice->address,
                'status' => 'at_post_office',
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 'at_post_office',
                'description' => "Đơn hàng đã đến bưu cục " . $postOffice->name,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật vị trí đơn hàng thành công',
                'location' => $postOffice->name,
                'address' => $postOffice->address,
                'coordinates' => $postOffice->coordinates,
            ]);
        }

        public function update(Request $request, Order $order)
        {
            Log::info('Updating order', ['order_id' => $order->id, 'request' => $request->all()]);

            try {
                $validated = $request->validate([
                    'post_office_id' => 'required|exists:post_offices,id',
                    'status' => 'required|in:pending,confirmed,picking_up,at_post_office,delivering,delivered',
                ]);

                $postOffice = PostOffice::findOrFail($validated['post_office_id']);

                DB::beginTransaction();

                // Update order
                $order->update([
                    'current_location_id' => $postOffice->id,
                    'current_location_type' => 'post_office',
                    'current_coordinates' => $postOffice->coordinates,
                    'current_location' => $postOffice->address,
                    'status' => $validated['status'],
                ]);

                // Create order status log
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $validated['status'],
                    'description' => "Đơn hàng đã được cập nhật trạng thái: " . ucfirst($validated['status']) . " tại " . $postOffice->name,
                    'updated_by' => Auth::id(),
                ]);

                // Create order location history
                OrderLocationHistory::create([
                    'order_id' => $order->id,
                    'location_type' => 'post_office',
                    'post_office_id' => $postOffice->id,
                    'address' => $postOffice->address,
                    'coordinates' => $postOffice->coordinates,
                    'status' => $validated['status'],
                    'timestamp' => now(),
                ]);

                DB::commit();

                // Dispatch OrderUpdated event
                event(new OrderUpdated($order));

                Log::info('Order updated successfully', ['order_id' => $order->id]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Cập nhật đơn hàng thành công',
                        'order' => $order->fresh()->load('locationHistory'),
                    ]);
                }

                return redirect()->route('orders.show', $order)->with('success', 'Cập nhật đơn hàng thành công');

            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Validation error while updating order', ['order_id' => $order->id, 'errors' => $e->errors()]);
                
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'errors' => $e->errors(),
                    ], 422);
                }

                return back()->withErrors($e->errors())->withInput();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating order', ['order_id' => $order->id, 'error' => $e->getMessage()]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Đã xảy ra lỗi khi cập nhật đơn hàng: ' . $e->getMessage(),
                    ], 500);
                }

                return back()->with('error', 'Đã xảy ra lỗi khi cập nhật đơn hàng: ' . $e->getMessage());
            }
        }
//import file excel orders 
    public function showImportForm()
    {
        // Fetch post offices from the database (this is just an example, adjust it to your needs)
        $postOffices = PostOffice::all();
        
        // Fetch user addresses if needed
        $userAddresses = UserAddress::all(); 

        // Pass the variables to the view
        return view('orders.import', compact('postOffices', 'userAddresses'));
    }

    public function import(Request $request)
    {
        try {
            $validationRules = [
                'excel_file' => 'required|mimes:xlsx,xls',
                'is_pickup_at_post_office' => 'required|boolean',
                'pickup_date' => 'required|date|after_or_equal:tomorrow',
                'sender_address_select' => 'required',
                'sender_name' => 'required_if:sender_address_select,new',
                'sender_phone' => 'required_if:sender_address_select,new',
                'sender_address' => 'required_if:sender_address_select,new',
            ];
    
            if ($request->is_pickup_at_post_office == 1) {
                $validationRules['pickup_location_id'] = 'required|exists:post_offices,id';
            } else {
                $validationRules['pickup_time'] = 'required';
            }
    
            $validatedData = $request->validate($validationRules);
    
            $senderData = [];
            if ($request->sender_address_select === 'new') {
                $senderCoordinates = $this->getCoordinates($request->sender_address);
                if (!$senderCoordinates) {
                    return back()->with('error', 'Không thể xác định tọa độ cho địa chỉ người gửi mới.')->withInput();
                }
    
                $senderData = [
                    'sender_name' => $request->sender_name,
                    'sender_phone' => $request->sender_phone,
                    'sender_address' => $request->sender_address,
                    'sender_coordinates' => $senderCoordinates,
                ];
    
                if ($request->has('save_sender_address') && $request->save_sender_address) {
                    UserAddress::create([
                        'user_id' => Auth::id(),
                        'name' => $request->sender_name,
                        'phone' => $request->sender_phone,
                        'address' => $request->sender_address,
                        'coordinates' => $senderCoordinates,
                    ]);
                }
            } else {
                $savedAddress = UserAddress::findOrFail($request->sender_address_select);
                $senderData = [
                    'sender_name' => $savedAddress->name,
                    'sender_phone' => $savedAddress->phone,
                    'sender_address' => $savedAddress->address,
                    'sender_coordinates' => $savedAddress->coordinates,
                ];
            }
    
            $commonData = array_merge($senderData, [
                'is_pickup_at_post_office' => $request->is_pickup_at_post_office,
                'pickup_location_id' => $request->pickup_location_id,
                'pickup_date' => $request->pickup_date,
                'pickup_time' => $request->pickup_time ?? '00:00',
            ]);
    
            $import = new OrdersImport($commonData);
            Excel::import($import, $request->file('excel_file'));
    
            return redirect()->route('orders.index')->with('success', 'Đơn hàng đã được nhập thành công.');
        } catch (\Exception $e) {
            Log::error('Lỗi khi nhập đơn hàng: ' . $e->getMessage());
            return back()->with('error', 'Đã xảy ra lỗi khi nhập đơn hàng: ' . $e->getMessage())->withInput();
        }
    }
    private function getCoordinates($address)
    {
        $response = Http::get('https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($address) . '.json', [
            'access_token' => env('MAPBOX_ACCESS_TOKEN'),
            'limit' => 1,
        ]);
    
        $data = $response->json();
    
        if (isset($data['features'][0]['center'])) {
            return $data['features'][0]['center'];
        }
    
        return null;
    }
    public function downloadTemplate()
    {
        return Excel::download(new OrderTemplateExport, 'order_import_template.xlsx');
    }

}