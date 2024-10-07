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
use App\Events\OrderCreated;
<<<<<<< HEAD
=======
use App\Events\OrderStatusUpdated;
>>>>>>> 0a21cfa (update 04/10)
use App\Models\UserAddress;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OrdersImport;
use App\Exports\OrderTemplateExport;
use App\Models\ProductCategory;
use App\Models\WarrantyPackage;
use App\Services\OrderAssignmentService;
use App\Models\OrderCancellationRequest;
use App\Notifications\OrderCancellationRequested;
use App\Listeners\SendOrderNotification;
use App\Events\ImportOrderCreated;
use App\Events\OrderDeleted;
class OrderController extends Controller
{
    protected $orderAssignmentService;

    public function __construct(OrderAssignmentService $orderAssignmentService)
        {
            $this->orderAssignmentService = $orderAssignmentService;
        }
    public function index(Request $request)
        {
            $user = Auth::user();
            $query = Order::query();
    
            if ($user->hasRole('admin')) {
                // Admin có thể xem tất cả đơn hàng
                if ($request->filled('post_office_id')) {
                    $query->whereHas('managingPostOffices', function ($q) use ($request) {
                        $q->where('post_offices.id', $request->post_office_id);
                    });
                }
            } elseif ($user->hasRole('buucuc')) {
                $postOffices = $user->postOffices;
                if ($postOffices->isEmpty()) {
                    // Nếu nhân viên bưu cục không được liên kết với bất kỳ bưu cục nào
                    return redirect()->route('home')->with('error', 'Bạn chưa được gán cho bất kỳ bưu cục nào. Vui lòng liên hệ quản trị viên.');
                }
                // Nhân viên bưu cục chỉ có thể xem đơn hàng của bưu cục của họ
                $query->whereHas('managingPostOffices', function ($q) use ($postOffices) {
                    $q->whereIn('post_offices.id', $postOffices->pluck('id'));
                });
            } else {
                // Nếu người dùng không có quyền xem đơn hàng
                return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập vào trang này.');
            }
    
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('sender_name', 'like', '%' . $request->search . '%')
                      ->orWhere('receiver_name', 'like', '%' . $request->search . '%')
                      ->orWhere('tracking_number', 'like', '%' . $request->search . '%');
                });
            }
    
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
    
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }
    
            $orders = $query->with('managingPostOffices')->latest()->paginate(10);
    
            $postOffices = $user->hasRole('admin') ? PostOffice::all() : $user->postOffices;
    
            return view('orders.index', compact('orders', 'postOffices'));
        }

    public function create()
        {
            $products = Product::all();
            $postOffices = PostOffice::all();
            $userAddresses = UserAddress::where('user_id', Auth::id())->get();
            $productCategories = ProductCategory::with('warrantyPackages')->get();
        
            return view('orders.create', compact('products', 'postOffices', 'userAddresses', 'productCategories'));
        }
<<<<<<< HEAD
        public function store(Request $request)
        {
            Log::info('Attempting to create a new order', ['request' => $request->all()]);
=======
    public function store(Request $request)
    {
        Log::info('Attempting to create a new order', ['request' => $request->all()]);
>>>>>>> 0a21cfa (update 04/10)
        
            try {
                DB::beginTransaction();
        
                $validationRules = [
                    'sender_address_select' => 'required',
                    'sender_name' => 'required_if:sender_address_select,new|string|max:255',
                    'sender_phone' => 'required_if:sender_address_select,new|string|max:20',
                    'sender_address' => 'required_if:sender_address_select,new|string|max:255',
                    'sender_coordinates' => 'required_if:sender_address_select,new',
                    'receiver_name' => 'required|string|max:255',
                    'receiver_phone' => 'required|string|max:20',
                    'receiver_address' => 'required|string|max:255',
                    'receiver_coordinates' => 'required',
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
                    $validatedData['sender_coordinates'] = $savedAddress->coordinates;
                } elseif ($request->has('save_sender_address') && $request->save_sender_address) {
                    UserAddress::create([
                        'user_id' => Auth::id(),
                        'name' => $validatedData['sender_name'],
                        'phone' => $validatedData['sender_phone'],
                        'address' => $validatedData['sender_address'],
                        'coordinates' => $this->parseCoordinates($validatedData['sender_coordinates']),
                    ]);
                }
        
                // Handle coordinates
                $senderCoordinates = $this->parseCoordinates($validatedData['sender_coordinates']);
                $receiverCoordinates = $this->parseCoordinates($validatedData['receiver_coordinates']);
        
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
                    'user_id' => auth()->id(),
                ]);
        
                if (!$order->save()) {
                    throw new \Exception('Failed to save order');
                }
        
                Log::info('Order saved successfully', [
                    'order_id' => $order->id,
                    'current_coordinates' => $order->current_coordinates,
                ]);
        
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
        
                // Assign order to post office based on pickup method
                if ($validatedData['is_pickup_at_post_office']) {
                    $postOffice = PostOffice::findOrFail($validatedData['pickup_location_id']);
                    $postOfficeCoordinates = $this->parseCoordinates($postOffice->coordinates);
                    $order->postOffices()->attach($postOffice->id);
                    $order->update([
                        'current_location_id' => $postOffice->id,
                        'current_location_type' => 'post_office',
                        'current_coordinates' => $postOfficeCoordinates,
                        'current_location' => $postOffice->address,
                    ]);
                    Log::info('Order assigned to selected post office', [
                        'order_id' => $order->id,
                        'post_office_id' => $postOffice->id,
                        'post_office_coordinates' => $postOfficeCoordinates,
                    ]);
                } else {
                    $assigned = $this->orderAssignmentService->assignOrderToPostOffice($order);
                    if (!$assigned) {
                        DB::rollBack();
                        Log::error('Failed to assign order to nearest post office', ['order_id' => $order->id]);
                        return response()->json(['error' => 'Failed to assign order to post office'], 500);
                    }
                }
        
                DB::commit();
                Log::info('Order creation transaction committed', ['order_id' => $order->id]);
<<<<<<< HEAD
<<<<<<< HEAD
        
                if ($request->ajax()) {
=======
                
=======
>>>>>>> 7d3f46b (update realtime redis)

                event(new OrderCreated($order));
                Log::info('OrderCreated event dispatched', ['order_id' => $order->id]);
                if ($request->ajax()) {
<<<<<<< HEAD
                    Log::info('Đang cố gắng gửi sự kiện OrderCreated', ['order_id' => $order->id]);
                    event(new OrderCreated($order));
                    Log::info('Đã gửi sự kiện OrderCreated', ['order_id' => $order->id]);
>>>>>>> 0a21cfa (update 04/10)
=======
>>>>>>> 7d3f46b (update realtime redis)
                    return response()->json([
                        'success' => true,
                        'message' => 'Đơn hàng đã được tạo thành công.',
                        'order' => [
                            'id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                        ],
                        'redirect' => route('customer.orders')
                    ]);
                } else {
                    return redirect()->route('customer.orders')->with('success', 'Đơn hàng đã được tạo thành công.');
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Lỗi khi gửi sự kiện OrderCreated: ' . $e->getMessage());
                Log::error('Error creating order', [
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                    'validated_data' => $validatedData ?? null
                ]);
        
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Đã xảy ra lỗi khi tạo đơn hàng: ' . $e->getMessage()
                    ], 500);
                } else {
<<<<<<< HEAD
=======

>>>>>>> 0a21cfa (update 04/10)
                    return redirect()->back()->with('error', 'Đã xảy ra lỗi khi tạo đơn hàng: ' . $e->getMessage())->withInput();
                }
            }
    }
        
<<<<<<< HEAD
        private function parseCoordinates($coordinates)
=======
    private function parseCoordinates($coordinates)
>>>>>>> 0a21cfa (update 04/10)
        {
            if (is_string($coordinates)) {
                $coords = json_decode($coordinates, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Invalid JSON format for coordinates');
                }
            } elseif (is_array($coordinates)) {
                $coords = $coordinates;
            } else {
                throw new \InvalidArgumentException('Invalid coordinate format');
            }
        
            if (count($coords) !== 2) {
                throw new \InvalidArgumentException('Coordinates must contain exactly two elements');
            }
        
            return array_map('floatval', $coords);
        }
    public function updateOrderAssignments()
    {
        $orders = Order::all();
        $orderAssignmentService = app(OrderAssignmentService::class);

        foreach ($orders as $order) {
            if ($order->is_pickup_at_post_office) {
                $postOffice = PostOffice::findOrFail($order->pickup_location_id);
                $postOfficeCoordinates = $this->parseCoordinates($postOffice->coordinates);
                $order->update([
                    'current_location_id' => $postOffice->id,
                    'current_location_type' => 'post_office',
                    'current_coordinates' => $postOfficeCoordinates,
                    'current_location' => $postOffice->address,
                ]);
            } else {
                $assigned = $orderAssignmentService->assignToNearestPostOffice($order);
                if (!$assigned) {
                    Log::error('Failed to assign order to nearest post office', ['order_id' => $order->id]);
                }
            }
        }

        return redirect()->route('orders.index')->with('success', 'Đã cập nhật vị trí bưu cục cho tất cả đơn hàng.');
    }
<<<<<<< HEAD
        
        

        // private function assignOrderToNearestPostOffice(Order $order)
        // {
        //     Log::info('Attempting to assign order to nearest post office', [
        //         'order_id' => $order->id,
        //         'sender_address' => $order->sender_address
        //     ]);
        
        //     // Trích xuất tỉnh/thành phố từ địa chỉ người gửi
        //     $senderProvince = $this->extractProvinceFromAddress($order->sender_address);
            
        //     // Tạo query builder
        //     $query = PostOffice::select('post_offices.*')
        //         ->selectRaw('ST_Distance_Sphere(
        //             point(longitude, latitude),
        //             point(?, ?)
        //         ) as distance', [$order->sender_coordinates[0], $order->sender_coordinates[1]]);
        
        //     // Nếu tìm thấy tỉnh/thành phố, ưu tiên tìm bưu cục trong tỉnh/thành phố đó
        //     if ($senderProvince) {
        //         $query->where('province', $senderProvince)
        //               ->orWhereNull('province');
        //     }
        
        //     // Tìm bưu cục gần nhất
        //     $nearestPostOffice = $query->orderBy('distance')
        //                                ->first();
        
        //     if (!$nearestPostOffice) {
        //         Log::error('No post office found', [
        //             'order_id' => $order->id,
        //             'sender_coordinates' => $order->sender_coordinates,
        //             'sender_province' => $senderProvince
        //         ]);
        //         return false;
        //     }
        
        //     // Gán đơn hàng cho bưu cục gần nhất
        //     $order->postOffices()->attach($nearestPostOffice->id);
        //     $order->update([
        //         'current_location_id' => $nearestPostOffice->id,
        //         'current_location_type' => 'post_office',
        //         'current_coordinates' => [$nearestPostOffice->longitude, $nearestPostOffice->latitude],
        //         'current_location' => $nearestPostOffice->address,
        //     ]);
        
        //     Log::info('Order assigned to nearest post office', [
        //         'order_id' => $order->id,
        //         'post_office_id' => $nearestPostOffice->id,
        //         'distance' => $nearestPostOffice->distance,
        //         'province_matched' => ($senderProvince && $senderProvince == $nearestPostOffice->province)
        //     ]);
        
        //     return true;
        // }
=======
    
>>>>>>> 0a21cfa (update 04/10)
        
        private function extractProvinceFromAddress($address)
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
                'Thừa Thiên Huế', 'Tiền Giang', 'Thành phố Hồ Chí Minh', 'Trà Vinh', 'Tuyên Quang',
                'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'
            ];
        
            $addressParts = explode(',', $address);
            $addressParts = array_map('trim', $addressParts);
        
            foreach ($addressParts as $part) {
                foreach ($provinces as $province) {
                    if (mb_stripos($part, $province) !== false) {
                        return $province;
                    }
                }
            }
        
            // Xử lý các trường hợp đặc biệt
            if (mb_stripos($address, 'TP HCM') !== false || mb_stripos($address, 'TP. HCM') !== false || mb_stripos($address, 'Hồ Chí Minh') !== false) {
                return 'Thành phố Hồ Chí Minh';
            }
        
            Log::warning('Province not found in address', ['address' => $address]);
            return null;
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
                'Bình Thuận' => 'BTH', 'Cà Mau' => 'CM', 'Cần Thơ' => 'CT', 'Cao Bằng' => 'CB', 'Đà Nẵng' => 'DN',
                'Đắk Lắk' => 'DL', 'Đắk Nông' => 'DNO', 'Điện Biên' => 'DB', 'Đồng Nai' => 'DA', 'Đồng Tháp' => 'DT',
                'Gia Lai' => 'GL', 'Hà Giang' => 'HG', 'Hà Nam' => 'HM', 'Hà Nội' => 'HN', 'Hà Tĩnh' => 'HT',
                'Hải Dương' => 'HD', 'Hải Phòng' => 'HP', 'Hậu Giang' => 'HU', 'Hòa Bình' => 'HB', 'Hưng Yên' => 'HY',
                'Khánh Hòa' => 'KH', 'Kiên Giang' => 'KG', 'Kon Tum' => 'KT', 'Lai Châu' => 'LC', 'Lâm Đồng' => 'LD',
                'Lạng Sơn' => 'LS', 'Lào Cai' => 'LO', 'Long An' => 'LA', 'Nam Định' => 'ND', 'Nghệ An' => 'NA',
                'Ninh Bình' => 'NB', 'Ninh Thuận' => 'NT', 'Phú Thọ' => 'PT', 'Phú Yên' => 'PY', 'Quảng Bình' => 'QB',
                'Quảng Nam' => 'QN', 'Quảng Ngãi' => 'QG', 'Quảng Ninh' => 'QI', 'Quảng Trị' => 'QT', 'Sóc Trăng' => 'ST',
                'Sơn La' => 'SL', 'Tây Ninh' => 'TN', 'Thái Bình' => 'TB', 'Thái Nguyên' => 'TNG', 'Thanh Hóa' => 'TH',
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
        // public function show(Order $order)
        // {
        //     $postOffices = PostOffice::all();
        //     return view('orders.show', compact('order', 'postOffices'));
        // }
        
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
        
                // Kiểm tra xem người dùng hiện tại có phải là admin không
                if (!Auth::user()->hasRole('admin')) {
                    return response()->json(['error' => 'Chỉ admin mới có quyền xóa đơn hàng.'], 403);
                }
        
                DB::beginTransaction();
        
                // Xóa các bản ghi liên quan (nếu có)
                // Ví dụ: $order->orderItems()->delete();
        
                $order->delete();
        
                DB::commit();
        
                event(new OrderDeleted($id));
        
                return response()->json(['success' => true, 'message' => 'Đơn hàng đã được xóa thành công.']);
       
                 return redirect()->route('orders.index')->with('success', 'Đã cập nhật vị trí bưu cục cho tất cả đơn hàng.');

        
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error deleting order: ' . $e->getMessage());
                return response()->json(['error' => 'Đã xảy ra lỗi khi xóa đơn hàng.'], 500);
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
            Log::info('Attempting to update order', ['order_id' => $order->id, 'user_id' => Auth::id()]);
    
            try {
                DB::beginTransaction();
    
                $validatedData = $request->validate([
                    'sender_name' => 'required|string|max:255',
                    'sender_phone' => 'required|string|max:20',
                    'sender_address' => 'required|string|max:255',
                    'receiver_name' => 'required|string|max:255',
                    'receiver_phone' => 'required|string|max:20',
                    'receiver_address' => 'required|string|max:255',
                    'is_pickup_at_post_office' => 'required|boolean',
                    'pickup_location_id' => 'required_if:is_pickup_at_post_office,1|exists:post_offices,id',
                    'pickup_date' => 'required|date',
                    'category_id' => 'required|exists:product_categories,id',
                    'warranty_package_id' => 'required|exists:warranty_packages,id',
                    'total_weight' => 'required|numeric|min:0',
                    'total_value' => 'required|numeric|min:0',
                    'total_cod' => 'required|numeric|min:0',
                    'products' => 'required|array|min:1',
                    'products.*.name' => 'required|string|max:255',
                    'products.*.quantity' => 'required|integer|min:1',
                    'products.*.price' => 'required|numeric|min:0',
                    'products.*.weight' => 'required|numeric|min:0',
                    'products.*.cod_amount' => 'required|numeric|min:0', 

                ]);
    
                Log::info('Validation passed', ['order_id' => $order->id, 'validated_data' => $validatedData]);
    
                // Cập nhật thông tin đơn hàng
                $order->update($validatedData);
    
                // Cập nhật sản phẩm
                $order->products()->detach();
                foreach ($validatedData['products'] as $productData) {
                    $product = Product::firstOrCreate(['name' => $productData['name']], [
                        'value' => $productData['price'],
                        'weight' => $productData['weight']
                    ]);
    
                    $order->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'weight' => $productData['weight'],
                        'cod_amount' => $productData['cod_amount'],

                    ]);
                }
    
                // Tính toán lại tổng khối lượng và giá trị
                $order->updateTotals();
    
                DB::commit();
    
                Log::info('Order updated successfully', ['order_id' => $order->id]);
                
                event(new OrderUpdated($order));
                return redirect()->route('orders.show', $order)->with('success', 'Đơn hàng đã được cập nhật thành công.');
    
            } catch (\Illuminate\Validation\ValidationException $e) {
                DB::rollBack();
                Log::error('Validation failed during order update', [
                    'order_id' => $order->id,
                    'errors' => $e->errors(),
                ]);
                return back()->withErrors($e->errors())->withInput();
    
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return back()->with('error', 'Đã xảy ra lỗi khi cập nhật đơn hàng. Vui lòng thử lại.')->withInput();
            }
        }
//import file excel orders 
        public function showImportForm()
        {
            $postOffices = PostOffice::all();
            $userAddresses = UserAddress::where('user_id', Auth::id())->get();

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

                $orderAssignmentService = app(OrderAssignmentService::class);
                $import = new OrdersImport($commonData, $orderAssignmentService);
                Excel::import($import, $request->file('excel_file'));
                foreach ($import->getImportedOrders() as $order) {
                    event(new ImportOrderCreated($order));
                }
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

    //Cancel Orders
    public function cancelOrder(Request $request, Order $order)
    {
        Log::info('Attempting to cancel order', ['order_id' => $order->id, 'user_id' => Auth::id()]);

        try {
            if (!$this->isOrderOwner($order)) {
                Log::warning('Unauthorized attempt to cancel order', [
                    'order_id' => $order->id,
                    'user_id' => Auth::id()
                ]);
                return back()->with('error', 'Bạn không có quyền hủy đơn hàng này.')->withInput();
            }

            if (!$this->isOrderCancellable($order)) {
                $errorMessage = $this->getCancellationErrorMessage($order);
                Log::warning('Attempt to cancel ineligible order', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'pickup_date' => $order->pickup_date
                ]);
                return back()->with('error', $errorMessage)->withInput();
            }

            DB::beginTransaction();

            // Validate the request
            $validatedData = $this->validateCancellationRequest($request);

            // Get the post office associated with the order
            $postOffice = $order->postOffices()->first();

            // Create cancellation request
            $cancellationRequest = $this->createCancellationRequest($order, $validatedData['reason'], $postOffice->id ?? null);
            
            if (!$cancellationRequest) {
                throw new \Exception('Failed to create cancellation request');
            }

            // Update order status
            $this->updateOrderStatus($order);

            // Notify relevant staff
            $this->notifyRelevantStaff($order);

            DB::commit();

            Log::info('Order cancellation request successful', [
                'order_id' => $order->id, 
                'cancellation_request_id' => $cancellationRequest->id,
                'post_office_id' => $cancellationRequest->post_office_id
            ]);

            return redirect()->route('orders.show', $order)->with('success', 'Yêu cầu hủy đơn hàng đã được gửi. Vui lòng đợi xác nhận từ nhân viên.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in order cancellation', [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Đã xảy ra lỗi khi gửi yêu cầu hủy đơn: ' . $e->getMessage())->withInput();
        }
    }


    private function isOrderCancellable(Order $order): bool
    {
        $cancellableStatuses = ['pending', 'assigned_to_post_office'];
        
        if (in_array($order->status, $cancellableStatuses)) {
            if ($order->status == 'assigned_to_post_office' && $order->pickup_date) {
                $oneDayBeforePickup = Carbon::parse($order->pickup_date)->subDay();
                $now = Carbon::now();
                
                if ($now->lte($oneDayBeforePickup)) {
                    Log::info('Order is cancellable: at least one day before pickup', ['order_id' => $order->id]);
                    return true;
                }
                
                Log::info('Order not cancellable: less than one day before pickup', ['order_id' => $order->id]);
                return false;
            }
            return true;
        }

        Log::info('Order not cancellable: invalid status', ['order_id' => $order->id, 'status' => $order->status]);
        return false;
    }

    private function getCancellationErrorMessage(Order $order): string
    {
        if ($order->status == 'assigned_to_post_office' && $order->pickup_date) {
            $oneDayBeforePickup = Carbon::parse($order->pickup_date)->subDay();
            $now = Carbon::now();
            
            if ($now->gt($oneDayBeforePickup)) {
                return 'Đơn hàng này không thể hủy do đã quá hạn hủy (phải hủy trước 1 ngày lấy hàng). Ngày lấy hàng: ' . $order->pickup_date->format('d/m/Y') . '.';
            }
        }

        switch ($order->status) {
            case 'at_post_office':
                return 'Đơn hàng đã đến bưu cục. Không thể hủy tại thời điểm này.';
            case 'delivering':
                return 'Đơn hàng đang trong quá trình giao. Không thể hủy tại thời điểm này.';
            case 'delivered':
                return 'Đơn hàng đã được giao thành công. Không thể hủy đơn hàng đã hoàn tất.';
            default:
                return 'Đơn hàng không thể hủy do đã được xử lý.';
        }
    }
    private function validateCancellationRequest(Request $request): array
    {
        return $request->validate([
            'reason' => 'required|string|in:changed_mind,found_better_deal,financial_reasons,other',
            'other_reason' => 'required_if:reason,other|string|nullable|max:255',
        ]);
    }

    

    private function createCancellationRequest(Order $order, string $reason, $postOfficeId): ?OrderCancellationRequest
    {
        $cancellationReason = $reason === 'other' ? request('other_reason') : $reason;

        try {
            $cancellationRequest = OrderCancellationRequest::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'post_office_id' => $postOfficeId,
                'reason' => $cancellationReason,
                'status' => 'pending',
            ]);

            Log::info('Cancellation request created', [
                'cancellation_request_id' => $cancellationRequest->id,
                'order_id' => $order->id,
                'post_office_id' => $postOfficeId,
                'reason' => $cancellationReason
            ]);

            return $cancellationRequest;
        } catch (\Exception $e) {
            Log::error('Failed to create cancellation request', [
                'order_id' => $order->id,
                'post_office_id' => $postOfficeId,
                'error_message' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function updateOrderStatus(Order $order): void
    {
        $order->update([
            'status' => 'cancellation_requested',
            'cancellation_requested_at' => now(),
        ]);
        Log::info('Order status updated for cancellation request', [
            'order_id' => $order->id, 
            'new_status' => 'cancellation_requested'
        ]);
    }

    private function notifyRelevantStaff(Order $order): void
    {
        // Implementation for notifying staff
        Log::info('Notifying staff about cancellation request', ['order_id' => $order->id]);
    }

    //Khách hàng view 
    public function customerOrders(Request $request)
    {
        try {
            $user = Auth::user();
            
            Log::info('User accessing customer orders page', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);
    
            // Lấy tất cả địa chỉ của user
            $userAddresses = $user->addresses()->get();
    
            Log::info('User addresses', [
                'user_id' => $user->id,
                'address_count' => $userAddresses->count(),
                'addresses' => $userAddresses->map(function($address) {
                    return [
                        'id' => $address->id,
                        'name' => $address->name,
                        'phone' => $address->phone
                    ];
                })
            ]);
    
            // Tạo query để lấy đơn hàng
            $query = Order::whereIn('sender_name', $userAddresses->pluck('name'))
                          ->whereIn('sender_phone', $userAddresses->pluck('phone'));
    
            // Log câu truy vấn SQL
            Log::info('SQL query for user orders', [
                'query' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
    
            // Áp dụng các bộ lọc
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('tracking_number', 'like', '%' . $searchTerm . '%')
                      ->orWhere('receiver_name', 'like', '%' . $searchTerm . '%');
                });
            }
    
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
    
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }
    
            // Đếm tổng số đơn hàng trước khi phân trang
            $totalOrders = $query->count();
    
            // Áp dụng phân trang
            $orders = $query->latest()->paginate(10);
    
            Log::info('Orders query result', [
                'user_id' => $user->id,
                'total_matching_orders' => $totalOrders,
                'orders_in_current_page' => $orders->count(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ]);
    
            if ($orders->isEmpty()) {
                Log::warning('No orders found for user', [
                    'user_id' => $user->id,
                    'filters' => $request->all()
                ]);
                
                $message = 'Không tìm thấy đơn hàng nào.';
                if ($request->filled('search') || $request->filled('status') || $request->filled('date')) {
                    $message .= ' Vui lòng thử lại với các bộ lọc khác.';
                } else {
                    $message .= ' Bạn chưa tạo đơn hàng nào.';
                }
                
                return view('orders.customer_orders', compact('orders'))->with('info', $message);
            }
    
            // Log mẫu dữ liệu đơn hàng
            $sampleOrders = $orders->take(5);
            Log::info('Sample orders data', [
                'sample_orders' => $sampleOrders->map(function($order) {
                    return [
                        'id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'sender_name' => $order->sender_name,
                        'sender_phone' => $order->sender_phone,
                        'created_at' => $order->created_at
                    ];
                })
            ]);
    
            return view('orders.customer_orders', compact('orders'));
        } catch (\Exception $e) {
            Log::error('Error fetching customer orders', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return redirect()->route('home')->with('error', 'Đã xảy ra lỗi khi tải dữ liệu đơn hàng. Vui lòng thử lại sau.');
        }
    }

    private function isOrderOwner(Order $order): bool
    {
        $user = Auth::user();
        
        // Kiểm tra nếu người dùng là admin
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Kiểm tra tất cả địa chỉ của người dùng
        $userAddresses = $user->addresses()->get();
        
        foreach ($userAddresses as $address) {
            if ($order->sender_name === $address->name && $order->sender_phone === $address->phone) {
                return true;
            }
        }
        
        // Log thông tin để debug
        Log::info('Order ownership check failed', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'order_sender_name' => $order->sender_name,
            'order_sender_phone' => $order->sender_phone,
            'user_addresses' => $userAddresses->toArray()
        ]);
        
        return false;
    }

    public function edit(Order $order)
    {
        // Kiểm tra quyền truy cập
        if (!Auth::user()->hasRole('admin') && !$this->isOrderOwner($order)) {
            return redirect()->route('orders.index')->with('error', 'Bạn không có quyền chỉnh sửa đơn hàng này.');
        }

        $postOffices = PostOffice::all();
        $productCategories = ProductCategory::with('warrantyPackages')->get();
        $warrantyPackages = WarrantyPackage::all();

        return view('orders.edit', compact('order', 'postOffices', 'productCategories', 'warrantyPackages'));
    }
    
    public function show(Order $order)
    {
        if (Auth::user()->hasRole('admin') || $this->isOrderOwner($order)) {
            $postOffices = PostOffice::all();
            return view('orders.show', compact('order', 'postOffices'));
        }

        abort(403, 'Bạn không có quyền xem đơn hàng này.');
    }
  
}