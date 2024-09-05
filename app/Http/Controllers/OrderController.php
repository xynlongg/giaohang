<?php

namespace App\Http\Controllers;

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

class OrderController extends Controller
{
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
        return view('orders.create', compact('products', 'postOffices'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'pickup_type' => 'required|in:post_office,home',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string|max:255',
            'sender_coordinates' => 'required|json',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string|max:255',
            'receiver_coordinates' => 'required|json',
            'pickup_location_id' => 'required_if:pickup_type,post_office|exists:post_offices,id',
            'pickup_date' => 'required_if:pickup_type,home|date|after:today',
            'pickup_time' => 'required_if:pickup_type,home|date_format:H:i',
            'delivery_date' => 'required|date|after:pickup_date',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.cod_amount' => 'required|numeric|min:0',
            'products.*.weight' => 'required|numeric|min:0',
        ]);

        if (empty($validatedData['products'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải chọn ít nhất một sản phẩm.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $pickupDateTime = $validatedData['pickup_type'] == 'home'
                ? Carbon::parse($validatedData['pickup_date'] . ' ' . $validatedData['pickup_time'])
                : Carbon::now();
            
            $trackingNumber = $this->generateTrackingNumber();
            $qrCode = QrCode::size(100)->generate($trackingNumber);

            $order = Order::create([
                'sender_name' => $validatedData['sender_name'],
                'sender_phone' => $validatedData['sender_phone'],
                'sender_address' => $validatedData['sender_address'],
                'sender_coordinates' => json_decode($validatedData['sender_coordinates'], true),
                'receiver_name' => $validatedData['receiver_name'],
                'receiver_phone' => $validatedData['receiver_phone'],
                'receiver_address' => $validatedData['receiver_address'],
                'receiver_coordinates' => json_decode($validatedData['receiver_coordinates'], true),
                'pickup_type' => $validatedData['pickup_type'],
                'pickup_location_id' => $validatedData['pickup_type'] == 'post_office' ? $validatedData['pickup_location_id'] : null,
                'pickup_date' => $pickupDateTime,
                'delivery_date' => Carbon::parse($validatedData['delivery_date']),
                'total_weight' => 0,
                'total_cod' => 0,
                'total_value' => 0,
                'shipping_fee' => 0,
                'total_amount' => 0,
                'status' => 'pending',
                'tracking_number' => $trackingNumber,
                'qr_code' => $qrCode,
                'current_location_type' => 'sender',
                'current_coordinates' => json_decode($validatedData['sender_coordinates'], true),
                'current_location' => $validatedData['sender_address'],
            ]);

            $totalWeight = 0;
            $totalCod = 0;
            $totalValue = 0;

            foreach ($validatedData['products'] as $productData) {
                $product = Product::findOrFail($productData['id']);

                $order->products()->attach($product->id, [
                    'quantity' => $productData['quantity'],
                    'cod_amount' => $productData['cod_amount'],
                    'weight' => $productData['weight'],
                ]);

                $totalWeight += $productData['weight'] * $productData['quantity'];
                $totalCod += $productData['cod_amount'] * $productData['quantity'];
                $totalValue += $product->value * $productData['quantity'];
            }

            $shippingFee = $this->calculateShippingFee($totalWeight, $validatedData['sender_address'], $validatedData['receiver_address']);

            $order->update([
                'total_weight' => $totalWeight,
                'total_cod' => $totalCod,
                'total_value' => $totalValue,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalCod + $shippingFee,
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'description' => 'Đơn hàng mới được tạo',
                'updated_by' => Auth::id() ?? 1,
            ]);

            // Lưu vị trí người gửi vào lịch sử
            OrderLocationHistory::create([
                'order_id' => $order->id,
                'location_type' => 'sender',
                'address' => $validatedData['sender_address'],
                'coordinates' => json_decode($validatedData['sender_coordinates'], true),
                'status' => 'order_created',
                'timestamp' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đơn hàng đã được tạo thành công.',
                'tracking_number' => $trackingNumber,
                'qr_code' => $qrCode,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi tạo đơn hàng: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Order $order)
    {
        $postOffices = PostOffice::all();
        return view('orders.show', compact('order', 'postOffices'));
    }
    

    private function calculateShippingFee($weight, $senderAddress, $receiverAddress)
    {
        $isSameProvince = $this->checkSameProvince($senderAddress, $receiverAddress);

        if ($isSameProvince) {
            $baseFee = 20000;
            $additionalFee = $weight > 5 ? ($weight - 5) * 10000 : 0;
        } else {
            $baseFee = 30000;
            $additionalFee = $weight > 5 ? ($weight - 5) * 15000 : 0;
        }

        return $baseFee + $additionalFee;
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
    
   
    private function generateTrackingNumber()
    {
        do {
            $number = strtoupper(Str::random(10));
        } while (Order::where('tracking_number', $number)->exists());

        return $number;
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
        $request->validate([
            'post_office_id' => 'required|exists:post_offices,id',
            'status' => 'required|in:pending,confirmed,picking_up,at_post_office,delivering,delivered',
        ]);

        $postOffice = PostOffice::findOrFail($request->post_office_id);

        $order->update([
            'current_location_id' => $postOffice->id,
            'current_location_type' => 'post_office',
            'current_coordinates' => $postOffice->coordinates,
            'current_location' => $postOffice->address,
            'status' => $request->status,
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $request->status,
            'description' => "Đơn hàng đã được cập nhật trạng thái: " . ucfirst($request->status) . " tại " . $postOffice->name,
            'updated_by' => Auth::id(),
        ]);

        $statusClass = $this->getStatusClass($request->status);

        // return response()->json([
        //     'success' => true,
        //     'location' => $postOffice->name,
        //     'address' => $postOffice->address,
        //     'coordinates' => $postOffice->coordinates,
        //     'status' => ucfirst($request->status),
        //     'status_class' => $statusClass,
        // ]);
        return redirect()->route('orders.index')->with('success', 'Cập nhật đơn hàng thành công');

    }

}