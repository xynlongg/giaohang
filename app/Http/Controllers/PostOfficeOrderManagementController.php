<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Role;
use App\Models\Shipper;
use App\Models\PostOffice;
use App\Models\OrderDistribution;
use App\Services\SmartOrderSortingService;
use App\Models\ProvincialWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\ShipperAssigned;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\OrderReassigned;
use App\Models\DistributionHandover; 
use App\Services\WarehouseDispatchService;



class PostOfficeOrderManagementController extends Controller
{
    protected $smartOrderSortingService;

    protected $warehouseDispatchService;  

    public function __construct(
        SmartOrderSortingService $smartOrderSortingService,
        WarehouseDispatchService $warehouseDispatchService  
    ) {
        $this->smartOrderSortingService = $smartOrderSortingService;
        $this->warehouseDispatchService = $warehouseDispatchService;  
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $postOffice = $user->postOffices()->first();

        if (!$postOffice) {
            return redirect()->route('dashboard')->with('error', 'Bạn chưa được gán cho bất kỳ bưu cục nào. Vui lòng liên hệ quản trị viên.');
        }

        $query = Order::whereHas('postOffices', function ($q) use ($postOffice) {
            $q->where('post_offices.id', $postOffice->id);
        })->with(['distributions' => function ($q) use ($postOffice) {
            $q->where('post_office_id', $postOffice->id)->with('shipper');
        }]);

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

        $orders = $query->latest()->paginate(10)->appends($request->all());

        $shippers = Shipper::whereHas('postOffices', function ($q) use ($postOffice) {
            $q->where('post_offices.id', $postOffice->id);
        })
            ->withCount(['activeOrders' => function ($query) use ($postOffice) {
                $query->whereHas('distributions', function ($q) use ($postOffice) {
                    $q->where('post_office_id', $postOffice->id);
                });
            }])
            ->having('active_orders_count', '<', 20)
            ->orderByDesc('attendance_score')
            ->orderByDesc('vote_score')
            ->get();

        return view('post_offices.orders.index', compact('orders', 'shippers', 'postOffice'));
    }

    public function assignShipper(Request $request, Order $order)
    {
        Log::info('Đang thực hiện gán shipper', ['order_id' => $order->id, 'request_data' => $request->all()]);

        try {
            $request->validate([
                'shipper_id' => 'required|exists:shippers,id'
            ]);

            $shipper = Shipper::findOrFail($request->shipper_id);
            $currentPostOffice = Auth::user()->postOffices()->first();

            if (!$currentPostOffice) {
                Log::error('Người dùng không được gán cho bưu cục nào', ['user_id' => Auth::id()]);
                return back()->with('error', 'Bạn không được gán cho bất kỳ bưu cục nào');
            }

            // Kiểm tra đơn hàng có thuộc bưu cục không
            $orderBelongsToPostOffice = DB::table('post_office_orders')
                ->where('order_id', $order->id)
                ->where('post_office_id', $currentPostOffice->id)
                ->exists();

            if (!$orderBelongsToPostOffice) {
                Log::error('Đơn hàng không thuộc về bưu cục hiện tại', [
                    'order_id' => $order->id,
                    'post_office_id' => $currentPostOffice->id
                ]);
                return back()->with('error', 'Đơn hàng không thuộc về bưu cục của bạn');
            }

            DB::beginTransaction();

            // Kiểm tra phân phối hiện có để gửi event nếu cần
            $existingDistribution = OrderDistribution::where('order_id', $order->id)->first();
            if ($existingDistribution && $existingDistribution->shipper_id !== $shipper->id) {
                event(new OrderReassigned($order, $existingDistribution->shipper_id));
            }

            // Cập nhật trạng thái đơn hàng
            if ($order->canUpdateStatusTo(Order::STATUS_PICKUP_ASSIGNED)) {
                $order->update([
                    'status' => Order::STATUS_PICKUP_ASSIGNED,
                    'current_location_id' => $currentPostOffice->id,
                    'current_location_type' => PostOffice::class,
                ]);

                Log::info('Cập nhật trạng thái đơn hàng thành công', [
                    'order_id' => $order->id,
                    'new_status' => Order::STATUS_PICKUP_ASSIGNED
                ]);
            } else {
                throw new \Exception('Không thể cập nhật trạng thái đơn hàng');
            }

            // Tạo hoặc cập nhật bản ghi phân phối đơn giản hóa theo cấu trúc bảng mới
            OrderDistribution::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'shipper_id' => $shipper->id,
                    'post_office_id' => $currentPostOffice->id,
                    'distributed_by' => Auth::id(),
                    'distributed_at' => now()
                ]
            );

            DB::commit();

            // Fire event
            event(new ShipperAssigned($order, $shipper));

            Log::info('Gán shipper thành công', [
                'order_id' => $order->id,
                'shipper_id' => $shipper->id,
                'post_office_id' => $currentPostOffice->id
            ]);

            return back()->with('success', "Đã gán shipper {$shipper->name} thành công cho đơn hàng #{$order->tracking_number}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi gán shipper', [
                'order_id' => $order->id,
                'shipper_id' => $request->shipper_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Có lỗi xảy ra khi gán shipper: ' . $e->getMessage());
        }
    }

    public function assignShipperToPreparedOrder(Request $request, Order $order)
    {
        Log::info('Attempting to assign shipper for delivery', ['order_id' => $order->id, 'request_data' => $request->all()]);

        try {
            $request->validate([
                'shipper_id' => 'required|exists:shippers,id'
            ]);

            $shipper = Shipper::findOrFail($request->shipper_id);
            $currentPostOffice = Auth::user()->postOffices()->first();

            if (!$currentPostOffice) {
                Log::error('Current user not associated with any post office', ['user_id' => Auth::id()]);
                return response()->json(['error' => 'Bạn không được gán cho bất kỳ bưu cục nào'], 422);
            }

            $orderBelongsToPostOffice = DB::table('post_office_orders')
                ->where('order_id', $order->id)
                ->where('post_office_id', $currentPostOffice->id)
                ->exists();

            if (!$orderBelongsToPostOffice) {
                Log::error('Order not associated with current post office', [
                    'order_id' => $order->id,
                    'post_office_id' => $currentPostOffice->id
                ]);
                return response()->json(['error' => 'Đơn hàng không thuộc về bưu cục của bạn'], 422);
            }

            DB::beginTransaction();

            // Cập nhật trạng thái đơn hàng thành "Đang giao hàng"
            $order->update([
                'status' => Order::STATUS_OUT_FOR_DELIVERY,
                'current_location_id' => $currentPostOffice->id,
                'current_location_type' => PostOffice::class,
            ]);

            // Cập nhật hoặc tạo mới bản ghi phân phối
            $order->distributions()->updateOrCreate(
                ['post_office_id' => $currentPostOffice->id],
                [
                    'shipper_id' => $shipper->id,
                    'distributed_by' => Auth::id(),
                    'distributed_at' => now(),
                    'type' => 'delivery' // Thêm trường này nếu bạn muốn phân biệt giữa lấy hàng và giao hàng
                ]
            );

            // Cập nhật bảng post_office_shippers
            DB::table('post_office_shippers')->updateOrInsert(
                [
                    'post_office_id' => $currentPostOffice->id,
                    'shipper_id' => $shipper->id
                ],
                [
                    'updated_at' => now()
                ]
            );

            DB::commit();

            Log::info('Shipper assigned successfully for delivery', [
                'order_id' => $order->id,
                'shipper_id' => $shipper->id,
                'post_office_id' => $currentPostOffice->id
            ]);

            return response()->json([
                'message' => 'Đã gán shipper để giao hàng cho đơn hàng #' . $order->tracking_number,
                'tracking_number' => $order->tracking_number,
                'shipper_name' => $shipper->name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign shipper for delivery', [
                'order_id' => $order->id,
                'shipper_id' => $request->shipper_id,
                'post_office_id' => $currentPostOffice->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Có lỗi xảy ra khi gán shipper cho giao hàng: ' . $e->getMessage()], 422);
        }
    }
    public function managePreparedOrders(Request $request)
    {
        try {
            $user = Auth::user();
            $postOffice = $user->postOffices()->first();

            // Kiểm tra user có được gán cho bưu cục không
            if (!$postOffice) {
                Log::error('Người dùng không được gán bưu cục', ['user_id' => $user->id]);
                return redirect()->route('dashboard')
                    ->with('error', 'Bạn chưa được gán cho bất kỳ bưu cục nào. Vui lòng liên hệ quản trị viên.');
            }

            Log::info('Thông tin bưu cục hiện tại:', [
                'id' => $postOffice->id,
                'tên' => $postOffice->name,
                'tỉnh' => $postOffice->province
            ]);

            // Lấy các đơn hàng đã đến bưu cục
            $query = Order::where('status', Order::STATUS_ARRIVED_AT_POST_OFFICE)
                ->where(function ($q) use ($postOffice) {
                    // Đơn hàng thuộc bưu cục hiện tại
                    $q->whereHas('postOffices', function($subquery) use ($postOffice) {
                        $subquery->where('post_offices.id', $postOffice->id);
                    })
                    // Hoặc current_location là bưu cục này
                    ->orWhere(function($subquery) use ($postOffice) {
                        $subquery->where('current_location_id', $postOffice->id)
                                ->where('current_location_type', PostOffice::class);
                    });
                });

            // Log truy vấn SQL để debug
            Log::info('Truy vấn SQL:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            $orders = $query->get();

            // Phân loại đơn hàng theo categories
            $shippingCategories = [
                'cung_quan' => collect([]),
                'noi_thanh_duoi_20km' => collect([]),
                'noi_thanh_tren_20km' => collect([]),
                'ngoai_thanh' => collect([])
            ];

            // Nhóm đơn nội thành dưới 20km theo quận
            $noithanhDuoi20kmByDistrict = [];

            // Xử lý từng đơn hàng
            foreach ($orders as $order) {
                try {
                    if ($order->shipping_type === 'cung_quan') {
                        $shippingCategories['cung_quan']->push($order);
                    } elseif ($order->shipping_type === 'ngoai_thanh') {
                        $shippingCategories['ngoai_thanh']->push($order);
                    } elseif ($order->shipping_type === 'noi_thanh') {
                        // Kiểm tra và log tọa độ
                        $current_coordinates = $order->current_coordinates ?? null;
                        $receiver_coordinates = $order->receiver_coordinates ?? null;

                        Log::info('Tọa độ đơn hàng:', [
                            'order_id' => $order->id,
                            'tọa_độ_hiện_tại' => $current_coordinates,
                            'tọa_độ_người_nhận' => $receiver_coordinates
                        ]);

                        if (!$current_coordinates || !$receiver_coordinates) {
                            Log::warning('Thiếu tọa độ cho đơn hàng', [
                                'order_id' => $order->id,
                                'tracking_number' => $order->tracking_number
                            ]);
                            continue;
                        }

                        $distance = $this->calculateDistance(
                            $current_coordinates[1],
                            $current_coordinates[0],
                            $receiver_coordinates[1],
                            $receiver_coordinates[0]
                        );

                        $order->calculated_distance = $distance;

                        Log::info('Khoảng cách tính toán cho đơn hàng', [
                            'order_id' => $order->id,
                            'khoảng_cách' => $distance,
                            'quận_người_nhận' => $order->receiver_district
                        ]);

                        if ($distance <= 20) {
                            $district = $order->receiver_district;
                            if (!isset($noithanhDuoi20kmByDistrict[$district])) {
                                $noithanhDuoi20kmByDistrict[$district] = collect();
                            }
                            $noithanhDuoi20kmByDistrict[$district]->push($order);
                        } else {
                            $shippingCategories['noi_thanh_tren_20km']->push($order);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Lỗi xử lý đơn hàng', [
                        'order_id' => $order->id,
                        'lỗi' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Lấy danh sách các bưu cục khác trong tỉnh/thành
            $postOffices = PostOffice::where('id', '!=', $postOffice->id)
                ->where(function ($query) use ($orders) {
                    foreach ($orders as $order) {
                        $query->orWhere(function ($q) use ($order) {
                            // Tìm theo quận/huyện
                            $q->where('district', 'LIKE', '%' . $order->receiver_district . '%')
                            // Hoặc tìm theo tỉnh/thành
                            ->orWhere('province', 'LIKE', '%' . $order->receiver_province . '%')
                            // Hoặc tìm theo phường/xã nếu có
                            ->when($order->receiver_ward, function ($query) use ($order) {
                                return $query->orWhere('ward', 'LIKE', '%' . $order->receiver_ward . '%');
                            });
                        });
                    }
                })
                ->get()
                ->groupBy(function ($postOffice) {
                    return $postOffice->district;
                });

            // Lấy danh sách shipper của bưu cục
            $shippers = Shipper::whereHas('postOffices', function ($q) use ($postOffice) {
                $q->where('post_offices.id', $postOffice->id);
            })
            ->withCount(['activeOrders' => function ($query) use ($postOffice) {
                $query->whereHas('distributions', function ($q) use ($postOffice) {
                    $q->where('post_office_id', $postOffice->id);
                });
            }])
            ->having('active_orders_count', '<', 20)
            ->orderByDesc('attendance_score')
            ->orderByDesc('vote_score')
            ->get();

            // Lấy danh sách kho tổng trong cùng tỉnh/thành
            $provincialWarehouses = ProvincialWarehouse::where('province', $postOffice->province)
                ->get();

            // Log thông tin tổng hợp
            Log::info('Tổng hợp dữ liệu', [
                'bưu_cục_hiện_tại' => $postOffice->name,
                'số_lượng_đơn_cùng_quận' => $shippingCategories['cung_quan']->count(),
                'số_lượng_đơn_nội_thành_dưới_20km' => array_sum(array_map(function ($district) {
                    return $district->count();
                }, $noithanhDuoi20kmByDistrict)),
                'số_lượng_đơn_nội_thành_trên_20km' => $shippingCategories['noi_thanh_tren_20km']->count(),
                'số_lượng_đơn_ngoại_thành' => $shippingCategories['ngoai_thanh']->count(),
                'số_lượng_shipper' => $shippers->count(),
                'số_lượng_kho_tổng' => $provincialWarehouses->count(),
                'số_lượng_bưu_cục_trong_tỉnh' => $postOffices->count()
            ]);

            return view('post_offices.orders.manage_prepared', compact(
                'shippingCategories',
                'noithanhDuoi20kmByDistrict',
                'shippers',
                'postOffice',
                'postOffices',
                'provincialWarehouses'
            ));

        } catch (\Exception $e) {
            Log::error('Lỗi trong managePreparedOrders', [
                'lỗi' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage());
        }
    }
    public function bulkDispatchToWarehouse(Request $request)
    {
        $selectedOrderIds = $request->input('selected_orders', []);
        $targetWarehouseId = $request->input('target_warehouse_id');
        $currentPostOffice = Auth::user()->postOffices()->first();

        if (!$currentPostOffice) {
            return response()->json(['error' => 'Bạn không được gán cho bất kỳ bưu cục nào'], 422);
        }

        if (empty($selectedOrderIds)) {
            return response()->json(['error' => 'Vui lòng chọn ít nhất một đơn hàng để điều phối'], 422);
        }

        if (empty($targetWarehouseId)) {
            return response()->json(['error' => 'Vui lòng chọn kho tổng đích'], 422);
        }

        DB::beginTransaction();

        try {
            $targetWarehouse = ProvincialWarehouse::findOrFail($targetWarehouseId);

            $dispatchedOrders = 0;
            $failedOrders = [];

            foreach ($selectedOrderIds as $orderId) {
                $order = Order::findOrFail($orderId);

                if (($order->shipping_type === 'ngoai_thanh' ||
                        ($order->shipping_type === 'noi_thanh' && $order->calculated_distance > 20)) &&
                    $order->sender_province === $targetWarehouse->province
                ) {

                    $order->update([
                        'status' => 'transferring_to_provincial_warehouse',
                        'current_location_id' => $targetWarehouse->id,
                        'current_location_type' => ProvincialWarehouse::class,
                    ]);

                    $dispatchedOrders++;

                    Log::info('Order successfully dispatched to provincial warehouse', [
                        'order_id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'warehouse_id' => $targetWarehouse->id
                    ]);
                } else {
                    $failedOrders[] = $order->tracking_number;
                }
            }

            DB::commit();

            $message = "Đã điều phối thành công {$dispatchedOrders} đơn hàng đến kho tổng {$targetWarehouse->name}.";
            if (!empty($failedOrders)) {
                $message .= " Các đơn hàng sau không thể điều phối: " . implode(', ', $failedOrders);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error dispatching orders to provincial warehouse', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Có lỗi xảy ra khi điều phối hàng loạt: ' . $e->getMessage()], 500);
        }
    }
    public function dispatchSingleToWarehouse(Request $request)
    {
        $orderId = $request->input('order_id');
        $warehouseId = $request->input('warehouse_id');

        try {
            $order = Order::findOrFail($orderId);
            $warehouse = ProvincialWarehouse::findOrFail($warehouseId);

            $eligibilityCheck = $this->checkOrderEligibility($order, $warehouse);

            if ($eligibilityCheck['eligible']) {
                DB::transaction(function () use ($order, $warehouse) {
                    if ($order->canUpdateStatusTo(Order::STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE)) {
                        $order->update([
                            'status' => Order::STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE,
                            'current_location_id' => $warehouse->id,
                            'current_location_type' => get_class($warehouse),
                        ]);

                        // Add entry to warehouse_orders table
                        DB::table('warehouse_orders')->insert([
                            'order_id' => $order->id,
                            'provincial_warehouse_id' => $warehouse->id,
                            'entered_at' => now(),
                            'status' => 'entered',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info('Order successfully dispatched to provincial warehouse', [
                            'order_id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                            'warehouse_id' => $warehouse->id,
                            'calculated_distance' => $order->calculated_distance
                        ]);
                    } else {
                        throw new \Exception('Không thể cập nhật trạng thái đơn hàng');
                    }
                });

                return response()->json([
                    'success' => true,
                    'message' => "Đã điều phối đơn hàng #{$order->tracking_number} đến kho tổng {$warehouse->name} thành công."
                ]);
            } else {
                Log::warning('Order not eligible for provincial warehouse dispatch', [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'reasons' => $eligibilityCheck['reasons'],
                    'calculated_distance' => $order->calculated_distance
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Đơn hàng không đủ điều kiện để điều phối đến kho tổng này.',
                    'details' => $eligibilityCheck['reasons']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error dispatching single order to provincial warehouse', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }

    private function checkOrderEligibility(Order $order, ProvincialWarehouse $warehouse)
    {
        $eligible = true;
        $reasons = [];

        if (!in_array($order->shipping_type, ['ngoai_thanh', 'noi_thanh'])) {
            $eligible = false;
            $reasons[] = "Loại vận chuyển không phù hợp (hiện tại: {$order->shipping_type})";
        }

        if ($order->shipping_type === 'noi_thanh' && $order->calculated_distance <= 20) {
            $eligible = false;
            $reasons[] = "Khoảng cách vận chuyển nội thành không đủ xa (hiện tại: " . number_format($order->calculated_distance, 2) . " km)";
        }

        if ($order->sender_province !== $warehouse->province) {
            $eligible = false;
            $reasons[] = "Tỉnh gửi hàng không khớp với kho tổng (gửi từ: {$order->sender_province}, kho tổng: {$warehouse->province})";
        }

        return [
            'eligible' => $eligible,
            'reasons' => $reasons
        ];
    }

    public function dispatchToLocalPostOffice(Request $request)
    {
        try {
            // Lấy dữ liệu đầu vào
            $selectedOrderIds = $request->input('selected_orders', []);
            $targetPostOfficeId = $request->input('target_post_office_id');
            $distributionStaffId = $request->input('local_distribution_staff_id');
            $currentPostOffice = Auth::user()->postOffices()->first();

            // Log dữ liệu request
            Log::info('Starting local dispatch process', [
                'selected_orders' => $selectedOrderIds,
                'target_post_office_id' => $targetPostOfficeId,
                'distribution_staff_id' => $distributionStaffId,
                'current_post_office' => $currentPostOffice ? $currentPostOffice->id : null,
                'request_data' => $request->all()
            ]);

            // Validation
            $validationErrors = [];

            if (!$currentPostOffice) {
                $validationErrors[] = 'Người dùng không được gán cho bưu cục nào';
                Log::error('Current post office not found', ['user_id' => Auth::id()]);
            }

            if (empty($selectedOrderIds)) {
                $validationErrors[] = 'Chưa chọn đơn hàng nào để điều phối';
                Log::error('No orders selected', ['selected_orders' => $selectedOrderIds]);
            }

            if (empty($targetPostOfficeId)) {
                $validationErrors[] = 'Chưa chọn bưu cục đích';
                Log::error('No target post office specified', [
                    'target_post_office_id' => $targetPostOfficeId
                ]);
            }

            if (empty($distributionStaffId)) {
                $validationErrors[] = 'Chưa chọn nhân viên phân phối';
                Log::error('No distribution staff specified', [
                    'distribution_staff_id' => $distributionStaffId
                ]);
            } else {
                $staff = User::find($distributionStaffId);
                if (!$staff) {
                    $validationErrors[] = 'Không tìm thấy nhân viên phân phối';
                    Log::error('Staff not found', ['staff_id' => $distributionStaffId]);
                } elseif (!$staff->hasRole('local_distribution_staff')) {
                    $validationErrors[] = 'Nhân viên được chọn không phải là nhân viên phân phối nội bộ';
                    Log::error('Invalid staff role', [
                        'staff_id' => $distributionStaffId,
                        'roles' => $staff->roles->pluck('name')
                    ]);
                }
            }

            if (!empty($validationErrors)) {
                Log::error('Validation failed', [
                    'errors' => $validationErrors,
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Thiếu thông tin cần thiết để điều phối: ' . implode(', ', $validationErrors),
                    'validation_errors' => $validationErrors
                ], 422);
            }

            DB::beginTransaction();

            try {
                $targetPostOffice = PostOffice::findOrFail($targetPostOfficeId);
                $dispatchedOrders = 0;
                $failedOrders = [];

                foreach ($selectedOrderIds as $orderId) {
                    try {
                        $order = Order::findOrFail($orderId);

                        Log::info('Processing order', [
                            'order_id' => $orderId,
                            'tracking_number' => $order->tracking_number
                        ]);

                        if (!in_array($order->shipping_type, ['noi_thanh', 'cung_quan'])) {
                            throw new \Exception('Loại vận chuyển không phù hợp cho phân phối nội bộ');
                        }

                        // Tính khoảng cách vận chuyển
                        $distance = $this->calculateDistance(
                            $currentPostOffice->latitude,
                            $currentPostOffice->longitude,
                            $targetPostOffice->latitude,
                            $targetPostOffice->longitude
                        );

                        // Kiểm tra khoảng cách cho đơn nội thành
                        if ($order->shipping_type === 'noi_thanh' && $distance > 20) {
                            throw new \Exception('Đơn hàng nội thành vượt quá 20km, cần phân phối bởi nhân viên phân phối chung');
                        }

                        // Tạo bản ghi phân phối
                        $handover = DistributionHandover::create([
                            'order_id' => $order->id,
                            'distribution_staff_id' => $distributionStaffId,
                            'origin_post_office_id' => $currentPostOffice->id,
                            'destination_post_office_id' => $targetPostOffice->id,
                            'destination_warehouse_id' => null,
                            'shipping_type' => $order->shipping_type,
                            'status' => 'in_transit',
                            'distance' => $distance,
                            'assigned_at' => now(),
                            'completed_at' => null 
                        ]);

                        // Cập nhật trạng thái đơn hàng
                        $order->update([
                            'status' => Order::STATUS_TRANSFERRING_TO_DELIVERY_POST_OFFICE, 
                            'current_location_id' => $currentPostOffice->id,
                            'current_location_type' => PostOffice::class,
                        ]);

                        $dispatchedOrders++;

                        Log::info('Order dispatched successfully', [
                            'order_id' => $order->id,
                            'tracking_number' => $order->tracking_number,
                            'handover_id' => $handover->id,
                            'target_post_office' => $targetPostOffice->id,
                            'distance' => $distance
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to dispatch order', [
                            'order_id' => $orderId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        $failedOrders[] = [
                            'tracking_number' => isset($order) ? $order->tracking_number : $orderId,
                            'reason' => $e->getMessage()
                        ];
                    }
                }

                DB::commit();

                $message = "Đã điều phối thành công {$dispatchedOrders} đơn hàng đến bưu cục {$targetPostOffice->name}";
                $response = [
                    'success' => true,
                    'message' => $message
                ];

                if (!empty($failedOrders)) {
                    $response['failed_orders'] = $failedOrders;
                }

                Log::info('Local dispatch completed', [
                    'dispatched_count' => $dispatchedOrders,
                    'failed_count' => count($failedOrders),
                    'target_post_office' => $targetPostOffice->name
                ]);

                return response()->json($response);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Transaction failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Có lỗi xảy ra trong quá trình điều phối: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Có lỗi không mong đợi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }


    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // in kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return $distance;
    }
    public function processSmartSorting(Request $request)
    {
        $user = Auth::user();
        $postOffice = $user->postOffices()->first();

        if (!$postOffice) {
            return response()->json(['error' => 'Bạn chưa được gán cho bất kỳ bưu cục nào.'], 400);
        }

        try {
            DB::beginTransaction();

            $this->smartOrderSortingService->sortOrdersAtPostOffice($postOffice);

            DB::commit();

            return response()->json(['message' => 'Phân loại đơn hàng thành công.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi phân loại đơn hàng: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi phân loại đơn hàng.'], 500);
        }
    }
    public function bulkDispatch(Request $request)
    {
        $selectedOrderIds = $request->input('selected_orders', []);
        $currentPostOffice = Auth::user()->postOffices()->first();

        if (!$currentPostOffice) {
            return response()->json(['error' => 'Bạn không được gán cho bất kỳ bưu cục nào'], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($selectedOrderIds as $orderId) {
                $order = Order::findOrFail($orderId);

                switch ($order->shipping_type) {
                    case 'cung_quan':
                        $this->handleCungQuanOrder($order, $currentPostOffice);
                        break;
                    case 'noi_thanh':
                        $this->handleNoiThanhOrder($order, $currentPostOffice);
                        break;
                    case 'ngoai_thanh':
                        $this->handleNgoaiThanhOrder($order, $currentPostOffice);
                        break;
                    default:
                        throw new \Exception('Loại vận chuyển không hợp lệ');
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã điều phối thành công các đơn hàng đã chọn.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi điều phối hàng loạt:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Có lỗi xảy ra khi điều phối hàng loạt: ' . $e->getMessage()], 500);
        }
    }

    private function handleCungQuanOrder(Order $order, PostOffice $currentPostOffice)
    {
        // Gán shipper trực tiếp cho đơn hàng
        $shipper = $this->assignShipperToOrder($order, $currentPostOffice);
        $order->update([
            'status' => 'assigned_to_shipper',
            'current_location_id' => $currentPostOffice->id,
            'current_location_type' => PostOffice::class,
        ]);
        Log::info('Đơn hàng cùng quận đã được gán cho shipper', [
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'post_office_id' => $currentPostOffice->id
        ]);
    }

    private function handleNoiThanhOrder(Order $order, PostOffice $currentPostOffice)
    {
        $distance = $this->calculateDistance(
            $currentPostOffice->latitude,
            $currentPostOffice->longitude,
            $order->receiver_latitude,
            $order->receiver_longitude
        );

        if ($distance <= 20) {
            // Chuyển trực tiếp đến bưu cục gần nhất với người nhận
            $nearestPostOffice = $this->findNearestPostOffice($order->receiver_latitude, $order->receiver_longitude);
            $order->update([
                'status' => 'transferring_to_delivery_post_office',
                'current_location_id' => $nearestPostOffice->id,
                'current_location_type' => PostOffice::class,
            ]);
            Log::info('Đơn hàng nội thành được chuyển trực tiếp đến bưu cục gần nhất', [
                'order_id' => $order->id,
                'post_office_id' => $nearestPostOffice->id
            ]);
        } else {
            // Chuyển qua kho tổng
            $this->transferToProvincialWarehouse($order, $currentPostOffice);
        }
    }

    private function handleNgoaiThanhOrder(Order $order, PostOffice $currentPostOffice)
    {
        // Chuyển qua kho tổng
        $this->transferToProvincialWarehouse($order, $currentPostOffice);
    }

    private function assignShipperToOrder(Order $order, PostOffice $postOffice)
    {
        $shipper = Shipper::whereHas('postOffices', function ($query) use ($postOffice) {
            $query->where('post_offices.id', $postOffice->id);
        })
            ->orderBy('active_orders_count')
            ->first();

        if (!$shipper) {
            throw new \Exception('Không tìm thấy shipper phù hợp');
        }

        $order->distributions()->create([
            'shipper_id' => $shipper->id,
            'post_office_id' => $postOffice->id,
            'status' => 'assigned',
        ]);

        return $shipper;
    }

    private function findNearestPostOffice($latitude, $longitude)
    {
        return PostOffice::select(DB::raw("*, 
            ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) as distance"))
            ->setBindings([$longitude, $latitude])
            ->orderBy('distance')
            ->first();
    }

    private function transferToProvincialWarehouse(Order $order, PostOffice $currentPostOffice)
    {
        $provincialWarehouse = ProvincialWarehouse::where('province', $currentPostOffice->province)->first();

        if (!$provincialWarehouse) {
            throw new \Exception('Không tìm thấy kho tổng cho tỉnh ' . $currentPostOffice->province);
        }

        $order->update([
            'status' => 'transferring_to_provincial_warehouse',
            'current_location_id' => $provincialWarehouse->id,
            'current_location_type' => ProvincialWarehouse::class,
        ]);

        Log::info('Đơn hàng được chuyển đến kho tổng tỉnh', [
            'order_id' => $order->id,
            'provincial_warehouse_id' => $provincialWarehouse->id
        ]);

        // Nếu là đơn hàng ngoại thành, chuyển tiếp đến kho tổng tỉnh đích
        if ($order->shipping_type === 'ngoai_thanh') {
            $destinationWarehouse = ProvincialWarehouse::where('province', $order->receiver_province)->first();

            if (!$destinationWarehouse) {
                throw new \Exception('Không tìm thấy kho tổng cho tỉnh đích ' . $order->receiver_province);
            }

            $order->update([
                'next_location_id' => $destinationWarehouse->id,
                'next_location_type' => ProvincialWarehouse::class,
            ]);

            Log::info('Đơn hàng ngoại thành sẽ được chuyển tiếp đến kho tổng tỉnh đích', [
                'order_id' => $order->id,
                'destination_warehouse_id' => $destinationWarehouse->id
            ]);
        }
    }

    public function bulkDispatchToProvincialWarehouse(Request $request)
    {
        try {
            // Validate đầu vào
            $validated = $request->validate([
                'selected_orders' => 'required|array',
                'target_warehouse_id' => 'required|exists:provincial_warehouses,id',
                'general_distribution_staff_id' => 'required|exists:users,id'
            ]);

            $currentPostOffice = Auth::user()->postOffices()->first();
            if (!$currentPostOffice) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bạn không được gán cho bưu cục nào'
                ], 422);
            }

            // Kiểm tra role của nhân viên
            $staff = User::find($validated['general_distribution_staff_id']);
            if (!$staff->hasRole('general_distribution_staff')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Nhân viên được chọn không phải là nhân viên phân phối chung'
                ], 422);
            }

            // Sử dụng service để xử lý điều phối
            $warehouseDispatchService = app(WarehouseDispatchService::class);
            $result = $warehouseDispatchService->dispatchToWarehouse(
                $validated['selected_orders'],
                $validated['target_warehouse_id'],
                $validated['general_distribution_staff_id'],
                $currentPostOffice
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'dispatched_count' => $result['dispatched'],
                'failed_orders' => $result['failed']
            ], $result['success'] ? 200 : 422);

        } catch (\Exception $e) {
            Log::error('Lỗi trong quá trình điều phối đến kho tổng', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDistributionStaff(Request $request)
    {
        try {
            $staff = collect(); // Khởi tạo collection rỗng
            $currentPostOffice = Auth::user()->postOffices()->first();
    
            if (!$currentPostOffice) {
                throw new \Exception('Không tìm thấy thông tin bưu cục của bạn');
            }
    
            Log::info('Getting distribution staff', [
                'user_id' => Auth::id(),
                'current_post_office_id' => $currentPostOffice->id,
                'request_params' => $request->all()
            ]);
    
            // Lấy danh sách nhân viên dựa theo loại vận chuyển
            if ($request->has('post_office_id')) {
                Log::info('Fetching local distribution staff for local delivery');
                
                // Chỉ lấy nhân viên phân phối nội bộ của bưu cục gửi
                $staff = DB::table('users')
                    ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name')
                    ->join('role_user', 'users.id', '=', 'role_user.user_id')
                    ->join('roles', 'role_user.role_id', '=', 'roles.id')
                    ->join('post_office_user', 'users.id', '=', 'post_office_user.user_id')
                    ->where('post_office_user.post_office_id', $currentPostOffice->id) // Chỉ lấy nhân viên của bưu cục gửi
                    ->where('roles.name', 'local_distribution_staff')
                    ->whereNotExists(function ($query) use ($request) {
                        $query->select(DB::raw(1))
                            ->from('post_office_user as pou')
                            ->where('pou.user_id', DB::raw('users.id'))
                            ->where('pou.post_office_id', $request->post_office_id); // Loại trừ nhân viên đã thuộc bưu cục đích
                    })
                    ->orderBy('users.name')
                    ->get();
    
            } else if ($request->has('warehouse_id')) {
                Log::info('Fetching general distribution staff for warehouse delivery');
    
                // Chỉ lấy nhân viên phân phối chung của bưu cục gửi 
                $staff = DB::table('users')
                    ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name')
                    ->join('role_user', 'users.id', '=', 'role_user.user_id')
                    ->join('roles', 'role_user.role_id', '=', 'roles.id')
                    ->join('post_office_user', 'users.id', '=', 'post_office_user.user_id')
                    ->where('post_office_user.post_office_id', $currentPostOffice->id) // Chỉ lấy nhân viên của bưu cục gửi
                    ->where('roles.name', 'general_distribution_staff')
                    ->orderBy('users.name')
                    ->get();
            }
    
            // Map qua danh sách nhân viên để hiển thị tên role dễ đọc hơn
            $roleDisplayNames = [
                'local_distribution_staff' => 'Nhân viên phân phối nội bộ',
                'general_distribution_staff' => 'Nhân viên phân phối chung'
            ];
    
            // Kiểm tra nếu không tìm thấy nhân viên phù hợp
            if ($staff->isEmpty()) {
                throw new \Exception('Không tìm thấy nhân viên phân phối cho bưu cục này');
            }
    
            return response()->json([
                'success' => true,
                'data' => $staff->map(function ($user) use ($roleDisplayNames) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => [$roleDisplayNames[$user->role_name] ?? $user->role_name]
                    ];
                })
            ]);
    
        } catch (\Exception $e) {
            Log::error('Error getting distribution staff:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
    
            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra khi lấy danh sách nhân viên: ' . $e->getMessage()
            ], 500);
        }
    }
}