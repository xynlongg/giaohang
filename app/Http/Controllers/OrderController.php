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
use App\Events\OrderStatusUpdated;
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
    public function store(Request $request)
    {
        Log::info('Đang cố gắng tạo đơn hàng mới', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $validationRules = [
                'sender_address_select' => 'required',
                'sender_name' => 'required_if:sender_address_select,new|string|max:255',
                'sender_phone' => 'required_if:sender_address_select,new|string|max:20',
                'sender_address' => 'required_if:sender_address_select,new|string|max:255',
                'sender_district' => 'required_if:sender_address_select,new|string|max:255',
                'sender_province' => 'required_if:sender_address_select,new|string|max:255',
                'sender_coordinates' => 'required_if:sender_address_select,new',
                'receiver_name' => 'required|string|max:255',
                'receiver_phone' => 'required|string|max:20',
                'receiver_address' => 'required|string|max:255',
                'receiver_district' => 'required|string|max:255',
                'receiver_province' => 'required|string|max:255',
                'receiver_coordinates' => 'required',
                'is_pickup_at_post_office' => 'required|boolean',
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

            Log::info('Dữ liệu đã được xác thực', ['validatedData' => $validatedData]);

            // Xử lý thông tin người gửi
            if ($validatedData['sender_address_select'] !== 'new') {
                $savedAddress = UserAddress::findOrFail($validatedData['sender_address_select']);
                $validatedData['sender_name'] = $savedAddress->name;
                $validatedData['sender_phone'] = $savedAddress->phone;
                $validatedData['sender_address'] = $savedAddress->address;
                $validatedData['sender_district'] = $savedAddress->district;
                $validatedData['sender_province'] = $savedAddress->province;
                $validatedData['sender_coordinates'] = $savedAddress->coordinates;
            } elseif ($request->has('save_sender_address') && $request->save_sender_address) {
                UserAddress::create([
                    'user_id' => Auth::id(),
                    'name' => $validatedData['sender_name'],
                    'phone' => $validatedData['sender_phone'],
                    'address' => $validatedData['sender_address'],
                    'district' => $validatedData['sender_district'],
                    'province' => $validatedData['sender_province'],
                    'coordinates' => $this->parseCoordinates($validatedData['sender_coordinates']),
                ]);
            }

            // Xử lý tọa độ
            $senderCoordinates = $this->parseCoordinates($validatedData['sender_coordinates']);
            $receiverCoordinates = $this->parseCoordinates($validatedData['receiver_coordinates']);

            // Xác định loại vận chuyển
            $shippingType = $this->determineShippingType(
                $validatedData['sender_district'],
                $validatedData['sender_province'],
                $validatedData['receiver_district'],
                $validatedData['receiver_province']
            );

            // Tìm bưu cục phù hợp
            $assignedPostOffice = $this->findSuitablePostOffice(
                $validatedData['sender_district'],
                $validatedData['sender_province'],
                $senderCoordinates
            );

            if (!$assignedPostOffice) {
                throw new \Exception('Không tìm thấy bưu cục phù hợp để xử lý đơn hàng.');
            }

            // Tính phí vận chuyển
            $distance = $this->calculateDistance(
                $senderCoordinates[1],
                $senderCoordinates[0],
                $receiverCoordinates[1],
                $receiverCoordinates[0]
            );
            $shippingFee = $this->calculateShippingFee($distance, $shippingType);

            Log::info('Đã tính phí vận chuyển', ['distance' => $distance, 'shippingFee' => $shippingFee, 'shippingType' => $shippingType]);

            // Lấy gói bảo hành
            $warrantyPackage = WarrantyPackage::findOrFail($validatedData['warranty_package_id']);

            // Tính tổng số lượng
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

            Log::info('Đã tính tổng số lượng', [
                'totalWeight' => $totalWeight,
                'totalCod' => $totalCod,
                'totalValue' => $totalValue,
                'totalAmount' => $totalAmount
            ]);

            // Tạo mã theo dõi
            $trackingNumber = $this->generateTrackingNumber($validatedData['receiver_province']);
            Log::info('Đã tạo mã theo dõi', ['trackingNumber' => $trackingNumber]);

            // Tạo đơn hàng
            $order = new Order([
                'is_pickup_at_post_office' => $validatedData['is_pickup_at_post_office'],
                'pickup_date' => $validatedData['pickup_date'] ?? null,
                'pickup_time' => $validatedData['pickup_time'] ?? null,
                'sender_name' => $validatedData['sender_name'],
                'sender_phone' => $validatedData['sender_phone'],
                'sender_address' => $validatedData['sender_address'],
                'sender_district' => $validatedData['sender_district'],
                'sender_province' => $validatedData['sender_province'],
                'sender_coordinates' => $senderCoordinates,
                'receiver_name' => $validatedData['receiver_name'],
                'receiver_phone' => $validatedData['receiver_phone'],
                'receiver_address' => $validatedData['receiver_address'],
                'receiver_district' => $validatedData['receiver_district'],
                'receiver_province' => $validatedData['receiver_province'],
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
                'current_location_id' => $assignedPostOffice->id,
                'current_location_type' => 'sender',
                'current_coordinates' => $senderCoordinates,
                'current_location' => $validatedData['sender_address'],
                'user_id' => auth()->id(),
                'shipping_type' => $shippingType,
            ]);

            if (!$order->save()) {
                throw new \Exception('Không thể lưu đơn hàng');
            }

            Log::info('Đã lưu đơn hàng thành công', [
                'order_id' => $order->id,
                'current_coordinates' => $order->current_coordinates,
                'current_location' => $order->current_location,
                'shipping_type' => $order->shipping_type,
            ]);

            // Gắn sản phẩm vào đơn hàng
            foreach ($validatedData['products'] as $productData) {
                $product = Product::findOrFail($productData['id']);
                $order->products()->attach($product->id, [
                    'quantity' => $productData['quantity'],
                    'cod_amount' => $productData['cod_amount'],
                    'weight' => $productData['weight'],
                ]);
                Log::info('Đã gắn sản phẩm vào đơn hàng', [
                    'orderId' => $order->id,
                    'productId' => $product->id,
                    'quantity' => $productData['quantity'],
                    'codAmount' => $productData['cod_amount'],
                    'weight' => $productData['weight']
                ]);
            }

            // Tạo mã QR
            $qrCode = QrCode::size(300)->generate($trackingNumber);

            $order->update([
                'qr_code' => $qrCode,
            ]);

            // Gán đơn hàng cho bưu cục
            $order->postOffices()->attach($assignedPostOffice->id);

            Log::info('Đã gán đơn hàng cho bưu cục', [
                'order_id' => $order->id,
                'post_office_id' => $assignedPostOffice->id,
            ]);

            DB::commit();
            Log::info('Đã hoàn tất giao dịch tạo đơn hàng', ['order_id' => $order->id]);

            event(new OrderCreated($order));
            Log::info('Đã gửi sự kiện OrderCreated', ['order_id' => $order->id]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đơn hàng đã được tạo thành công.',
                    'order' => [
                        'id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'shipping_type' => $order->shipping_type,
                        'current_location' => $order->current_location,
                        'current_coordinates' => $order->current_coordinates,
                    ],
                    'redirect' => route('customer.orders')
                ]);
            } else {
                return redirect()->route('customer.orders')->with('success', 'Đơn hàng đã được tạo thành công.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo đơn hàng', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return $this->sendErrorResponse($request, 'Đã xảy ra lỗi khi tạo đơn hàng: ' . $e->getMessage());
        }
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
    private function determineShippingType($senderDistrict, $senderProvince, $receiverDistrict, $receiverProvince)
    {
        $senderDistrict = $this->normalizeString($senderDistrict);
        $senderProvince = $this->normalizeString($senderProvince);
        $receiverDistrict = $this->normalizeString($receiverDistrict);
        $receiverProvince = $this->normalizeString($receiverProvince);

        if ($this->isSimilar($senderDistrict, $receiverDistrict) && $this->isSimilar($senderProvince, $receiverProvince)) {
            return 'cung_quan';
        } elseif ($this->isSimilar($senderProvince, $receiverProvince)) {
            return 'noi_thanh';
        } else {
            return 'ngoai_thanh';
        }
    }
    private function normalizeString($string)
    {
        $string = mb_strtolower($string, 'UTF-8');
        $string = preg_replace('/\s+/', '', $string);
        $string = str_replace(['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ'], 'a', $string);
        $string = str_replace(['è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ'], 'e', $string);
        $string = str_replace(['ì', 'í', 'ị', 'ỉ', 'ĩ'], 'i', $string);
        $string = str_replace(['ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ'], 'o', $string);
        $string = str_replace(['ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ'], 'u', $string);
        $string = str_replace(['ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ'], 'y', $string);
        $string = str_replace('đ', 'd', $string);
        return $string;
    }
    private function isSimilar($str1, $str2, $threshold = 80)
    {
        $str1 = $this->normalizeString($str1);
        $str2 = $this->normalizeString($str2);

        if ($str1 === $str2) {
            return true;
        }

        $levenshtein = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        $similarity = (1 - ($levenshtein / $maxLength)) * 100;

        return $similarity >= $threshold;
    }
    private function findSuitablePostOffice($senderDistrict, $senderProvince, $senderCoordinates)
    {
        // Tìm bưu cục trong cùng quận/huyện và tỉnh/thành phố
        $postOffice = PostOffice::where('district', $senderDistrict)
            ->where('province', $senderProvince)
            ->first();

        if ($postOffice) {
            return $postOffice;
        }

        // Nếu không tìm thấy, tìm bưu cục gần nhất dựa trên tọa độ
        $nearestPostOffice = PostOffice::selectRaw('*, 
                ST_Distance_Sphere(
                    point(longitude, latitude),
                    point(?, ?)
                ) as distance', [$senderCoordinates[0], $senderCoordinates[1]])
            ->orderBy('distance')
            ->first();

        return $nearestPostOffice;
    }



    private function parseCoordinates($coordinates)
    {
        if (is_string($coordinates)) {
            // Nếu là chuỗi, giả sử nó là JSON và cố gắng decode
            $decoded = json_decode($coordinates, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $coordinates = $decoded;
            } else {
                // Nếu không phải JSON, thử tách chuỗi
                $coordinates = explode(',', $coordinates);
            }
        }

        if (is_array($coordinates) && count($coordinates) === 2) {
            // Đảm bảo rằng cả hai giá trị đều là số
            $longitude = floatval($coordinates[0]);
            $latitude = floatval($coordinates[1]);
            return [$longitude, $latitude];
        }

        // Log lỗi nếu định dạng không hợp lệ
        Log::error('Invalid coordinate format', ['coordinates' => $coordinates]);
        throw new \InvalidArgumentException('Invalid coordinate format. Expected [longitude, latitude].');
    }


    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // Radius of the earth in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
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
            'An Giang' => 'AG',
            'Bà Rịa - Vũng Tàu' => 'BV',
            'Bắc Giang' => 'BG',
            'Bắc Kạn' => 'BK',
            'Bạc Liêu' => 'BL',
            'Bắc Ninh' => 'BN',
            'Bến Tre' => 'BT',
            'Bình Định' => 'BD',
            'Bình Dương' => 'BI',
            'Bình Phước' => 'BP',
            'Bình Thuận' => 'BTH',
            'Cà Mau' => 'CM',
            'Cần Thơ' => 'CT',
            'Cao Bằng' => 'CB',
            'Đà Nẵng' => 'DN',
            'Đắk Lắk' => 'DL',
            'Đắk Nông' => 'DNO',
            'Điện Biên' => 'DB',
            'Đồng Nai' => 'DA',
            'Đồng Tháp' => 'DT',
            'Gia Lai' => 'GL',
            'Hà Giang' => 'HG',
            'Hà Nam' => 'HM',
            'Hà Nội' => 'HN',
            'Hà Tĩnh' => 'HT',
            'Hải Dương' => 'HD',
            'Hải Phòng' => 'HP',
            'Hậu Giang' => 'HU',
            'Hòa Bình' => 'HB',
            'Hưng Yên' => 'HY',
            'Khánh Hòa' => 'KH',
            'Kiên Giang' => 'KG',
            'Kon Tum' => 'KT',
            'Lai Châu' => 'LC',
            'Lâm Đồng' => 'LD',
            'Lạng Sơn' => 'LS',
            'Lào Cai' => 'LO',
            'Long An' => 'LA',
            'Nam Định' => 'ND',
            'Nghệ An' => 'NA',
            'Ninh Bình' => 'NB',
            'Ninh Thuận' => 'NT',
            'Phú Thọ' => 'PT',
            'Phú Yên' => 'PY',
            'Quảng Bình' => 'QB',
            'Quảng Nam' => 'QN',
            'Quảng Ngãi' => 'QG',
            'Quảng Ninh' => 'QI',
            'Quảng Trị' => 'QT',
            'Sóc Trăng' => 'ST',
            'Sơn La' => 'SL',
            'Tây Ninh' => 'TN',
            'Thái Bình' => 'TB',
            'Thái Nguyên' => 'TNG',
            'Thanh Hóa' => 'TH',
            'Thừa Thiên Huế' => 'TT',
            'Tiền Giang' => 'TG',
            'Thành Phố Hồ Chí Minh' => 'HCM',
            'Hồ Chí Minh' => 'HCM',
            'Trà Vinh' => 'TV',
            'Tuyên Quang' => 'TQ',
            'Vĩnh Long' => 'VL',
            'Vĩnh Phúc' => 'VP',
            'Yên Bái' => 'YB'
        ];

        foreach ($provinces as $province => $code) {
            if (stripos($address, $province) !== false) {
                return $code;
            }
        }

        // Nếu không tìm thấy tỉnh thành phù hợp, trả về mã mặc định
        return 'XX';
    }


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


    private function checkSameProvince($address1, $address2)
    {
        $provinces = [
            'An Giang',
            'Bà Rịa - Vũng Tàu',
            'Bắc Giang',
            'Bắc Kạn',
            'Bạc Liêu',
            'Bắc Ninh',
            'Bến Tre',
            'Bình Định',
            'Bình Dương',
            'Bình Phước',
            'Bình Thuận',
            'Cà Mau',
            'Cần Thơ',
            'Cao Bằng',
            'Đà Nẵng',
            'Đắk Lắk',
            'Đắk Nông',
            'Điện Biên',
            'Đồng Nai',
            'Đồng Tháp',
            'Gia Lai',
            'Hà Giang',
            'Hà Nam',
            'Hà Nội',
            'Hà Tĩnh',
            'Hải Dương',
            'Hải Phòng',
            'Hậu Giang',
            'Hòa Bình',
            'Hưng Yên',
            'Khánh Hòa',
            'Kiên Giang',
            'Kon Tum',
            'Lai Châu',
            'Lâm Đồng',
            'Lạng Sơn',
            'Lào Cai',
            'Long An',
            'Nam Định',
            'Nghệ An',
            'Ninh Bình',
            'Ninh Thuận',
            'Phú Thọ',
            'Phú Yên',
            'Quảng Bình',
            'Quảng Nam',
            'Quảng Ngãi',
            'Quảng Ninh',
            'Quảng Trị',
            'Sóc Trăng',
            'Sơn La',
            'Tây Ninh',
            'Thái Bình',
            'Thái Nguyên',
            'Thanh Hóa',
            'Thừa Thiên Huế',
            'Tiền Giang',
            'TP Hồ Chí Minh',
            'Trà Vinh',
            'Tuyên Quang',
            'Vĩnh Long',
            'Vĩnh Phúc',
            'Yên Bái'
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
                'sender_district' => 'required_if:sender_address_select,new|string|max:255',
                'sender_province' => 'required_if:sender_address_select,new|string|max:255',
                'sender_coordinates' => 'required_if:sender_address_select,new',

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
                        'district' => $request->district,
                        'country' => $request->country,
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
                'addresses' => $userAddresses->map(function ($address) {
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
                'sample_orders' => $sampleOrders->map(function ($order) {
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
