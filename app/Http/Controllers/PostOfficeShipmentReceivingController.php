<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;
use App\Models\DistributionHandover;
use App\Models\Shipper;
use App\Models\PostOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostOfficeShipmentReceivingController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            $postOffice = $user->postOffices()->first();
    
            if (!$postOffice) {
                return redirect()->route('dashboard')
                    ->with('error', 'Bạn chưa được gán cho bất kỳ bưu cục nào.');
            }
    
            // Query incoming handovers
            $incomingHandovers = DistributionHandover::with([
                    'order', 
                    'originPostOffice', 
                    'distributionStaff',
                    'order.currentLocation'
                ])
                ->where('destination_post_office_id', $postOffice->id)
                ->whereIn('status', ['in_transit', 'completed'])
                ->whereHas('order', function($query) {
                    $query->where('status', 'arrived_waiting_confirmation');
                })
                ->latest('created_at')
                ->paginate(10);
    
            // Query confirmed orders
            $confirmedOrders = Order::with([
                    'currentLocation',
                    'sender',
                    'receiver',
                    'lastHandover.originPostOffice'
                ])
                ->where('current_location_id', $postOffice->id)
                ->where('current_location_type', PostOffice::class)
                ->where('status', 'confirmed_at_destination')
                ->latest('updated_at')
                ->paginate(10);
    
            $availableShippers = Shipper::whereHas('postOffices', function($q) use ($postOffice) {
                    $q->where('post_offices.id', $postOffice->id);
                })
                ->withCount(['activeOrders' => function($q) {
                    $q->whereIn('status', ['assigned', 'in_delivery', 'in_transit']);
                }])
                ->having('active_orders_count', '<', 20)
                ->orderBy('active_orders_count')
                ->get();
    
            return view('post_offices.receiving.index', compact(
                'incomingHandovers',
                'confirmedOrders',
                'availableShippers',
                'postOffice'
            ));
    
        } catch (\Exception $e) {
            Log::error('Error loading receiving page:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return redirect()->route('dashboard')
                ->with('error', 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
        }
    }
    
    public function confirmReceiptAndAssignShipper(Request $request)
    {
        try {
            DB::beginTransaction();
    
            Log::info('Starting confirmation process', [
                'request_data' => $request->all()
            ]);
    
            $request->validate([
                'handover_ids' => 'required|array',
                'handover_ids.*' => 'required|exists:distribution_handovers,id',
                'shipper_id' => 'required|exists:shippers,id'
            ]);
    
            $user = Auth::user();
            $postOffice = $user->postOffices()->first();
    
            if (!$postOffice) {
                throw new \Exception('Bạn chưa được gán cho bất kỳ bưu cục nào');
            }
    
            // Get handovers with related data
            $handovers = DistributionHandover::with(['order', 'originWarehouse'])
                ->whereIn('id', $request->handover_ids)
                ->where('destination_post_office_id', $postOffice->id)
                ->whereHas('order', function($query) {
                    $query->where('status', Order::STATUS_ARRIVED_WAITING_CONFIRMATION);
                })
                ->get();
    
            if ($handovers->isEmpty()) {
                throw new \Exception('Không tìm thấy đơn hàng cần xác nhận');
            }
    
            $shipper = Shipper::findOrFail($request->shipper_id);
            $currentTime = Carbon::now();
    
            // Check shipper post office relation
            DB::table('post_office_shippers')->updateOrInsert(
                [
                    'post_office_id' => $postOffice->id,
                    'shipper_id' => $shipper->id
                ],
                [
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    'updated_at' => $currentTime
                ]
            );
    
            $confirmedCount = 0;
            $failedHandovers = [];
    
            foreach ($handovers as $handover) {
                try {
                    DB::beginTransaction();
    
                    $order = $handover->order;
    
                    if ($order->canUpdateStatusTo(Order::STATUS_OUT_FOR_DELIVERY)) {
                        // Update order status
                        $order->update([
                            'status' => Order::STATUS_OUT_FOR_DELIVERY,
                            'current_location_id' => $postOffice->id,
                            'current_location_type' => PostOffice::class
                        ]);
    
                        // Create delivery record
                        DB::table('order_deliveries')->insert([
                            'order_id' => $order->id,
                            'shipper_id' => $shipper->id,
                            'post_office_id' => $postOffice->id,
                            'status' => 'assigned',
                            'assigned_at' => $currentTime,
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime
                        ]);
    
                        // Update handover status
                        $handover->update([
                            'status' => 'completed',
                            'origin_warehouse_id' => $handover->originWarehouse?->id,
                            'completed_at' => $currentTime
                        ]);
    
                        DB::commit();
                        $confirmedCount++;
    
                        Log::info('Successfully confirmed handover', [
                            'handover_id' => $handover->id,
                            'order_id' => $order->id
                        ]);
                    } else {
                        throw new \Exception('Không thể chuyển trạng thái đơn hàng sang giao hàng');
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error processing handover', [
                        'handover_id' => $handover->id,
                        'error' => $e->getMessage()
                    ]);
    
                    $failedHandovers[] = [
                        'id' => $handover->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
    
            if ($confirmedCount === 0) {
                throw new \Exception('Không có đơn hàng nào được xử lý thành công');
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => "Đã xác nhận {$confirmedCount} đơn hàng và phân công cho shipper {$shipper->name}", 
                'confirmed_count' => $confirmedCount,
                'failed_handovers' => $failedHandovers
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in confirmation process:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false, 
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmArrival(Request $request)
    {
        try {
            Log::info('Bắt đầu quá trình xác nhận đơn hàng đến', [
                'request_data' => $request->all()
            ]);

            $request->validate([
                'handover_ids' => 'required|array',
                'handover_ids.*' => 'required|exists:distribution_handovers,id',
            ]);

            $user = Auth::user();
            $postOffice = $user->postOffices()->first();

            if (!$postOffice) {
                throw new \Exception('Bạn chưa được gán cho bất kỳ bưu cục nào');
            }

            DB::beginTransaction();

            $handovers = DistributionHandover::with(['order', 'originPostOffice'])
                ->whereIn('id', $request->handover_ids)
                ->where('destination_post_office_id', $postOffice->id)
                ->whereHas('order', function($query) {
                    $query->where('status', Order::STATUS_ARRIVED_WAITING_CONFIRMATION);
                })
                ->get();

            if ($handovers->isEmpty()) {
                throw new \Exception('Không tìm thấy đơn hàng cần xác nhận');
            }

            $confirmedCount = 0;
            $failedHandovers = [];
            $currentTime = Carbon::now();

            foreach ($handovers as $handover) {
                try {
                    $order = $handover->order;

                    if ($order->canUpdateStatusTo(Order::STATUS_CONFIRMED_AT_DESTINATION)) {
                        // Cập nhật trạng thái đơn hàng
                        DB::table('orders')
                            ->where('id', $order->id)
                            ->update([
                                'status' => Order::STATUS_CONFIRMED_AT_DESTINATION,
                                'updated_at' => $currentTime
                            ]);

                        // Cập nhật handover
                        DB::table('distribution_handovers')
                            ->where('id', $handover->id)
                            ->update([
                                'status' => 'completed',
                                'completed_at' => $currentTime
                            ]);

                        // Cập nhật hoặc tạo post_office_orders
                        DB::table('post_office_orders')->updateOrInsert(
                            [
                                'order_id' => $order->id,
                                'post_office_id' => $postOffice->id
                            ],
                            [
                                'created_at' => $currentTime,
                                'updated_at' => $currentTime
                            ]
                        );

                        // Xử lý coordinates trước khi insert
                        $coordinates = null;
                        if (!empty($postOffice->coordinates)) {
                            if (is_array($postOffice->coordinates)) {
                                $coordinates = json_encode($postOffice->coordinates);
                            } else {
                                $coordinates = $postOffice->coordinates;
                            }
                        }

                        // Thêm vào bảng order_location_histories
                        DB::table('order_location_histories')->insert([
                            'order_id' => $order->id,
                            'location_type' => PostOffice::class,
                            'location_id' => $postOffice->id,
                            'address' => $postOffice->address ?? '',
                            'coordinates' => $coordinates,
                            'status' => Order::STATUS_CONFIRMED_AT_DESTINATION,
                            'timestamp' => $currentTime,
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime
                        ]);

                        $confirmedCount++;

                        Log::info('Đã xác nhận đơn hàng đến bưu cục thành công', [
                            'handover_id' => $handover->id,
                            'order_id' => $order->id,
                            'order_status' => Order::STATUS_CONFIRMED_AT_DESTINATION,
                            'post_office' => $postOffice->name
                        ]);

                    } else {
                        throw new \Exception('Không thể chuyển trạng thái đơn hàng');
                    }
                } catch (\Exception $e) {
                    Log::error('Lỗi xử lý đơn hàng', [
                        'handover_id' => $handover->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $failedHandovers[] = [
                        'id' => $handover->id,
                        'tracking_number' => $handover->order->tracking_number,
                        'error' => $e->getMessage()
                    ];
                }
            }

            if ($confirmedCount === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Không có đơn hàng nào được xử lý thành công',
                    'failed_handovers' => $failedHandovers
                ]);
            }

            DB::commit();

            $message = "Đã xác nhận {$confirmedCount} đơn hàng thành công";
            if (!empty($failedHandovers)) {
                $message .= ". " . count($failedHandovers) . " đơn hàng xử lý thất bại.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'confirmed_count' => $confirmedCount,
                'failed_handovers' => $failedHandovers
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi trong quá trình xác nhận:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignShipper(Request $request)
    {
        try {
            Log::info('Bắt đầu quá trình phân công shipper', [
                'request_data' => $request->all()
            ]);
    
            $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'required|exists:orders,id',
                'shipper_id' => 'required|exists:shippers,id'
            ]);
    
            $user = Auth::user();
            $postOffice = $user->postOffices()->first();
    
            if (!$postOffice) {
                throw new \Exception('Bạn chưa được gán cho bất kỳ bưu cục nào');
            }
    
            DB::beginTransaction();
    
            $orders = Order::whereIn('id', $request->order_ids)
                ->where('current_location_id', $postOffice->id)
                ->where('current_location_type', PostOffice::class)
                ->where('status', Order::STATUS_CONFIRMED_AT_DESTINATION)
                ->get();
    
            if ($orders->isEmpty()) {
                throw new \Exception('Không tìm thấy đơn hàng cần phân công');
            }
    
            $shipper = Shipper::findOrFail($request->shipper_id);
            $currentTime = Carbon::now();
    
            // Kiểm tra số lượng đơn hàng hiện tại của shipper
            $activeOrderCount = DB::table('order_deliveries')
                ->where('shipper_id', $shipper->id)
                ->whereIn('status', ['assigned', 'in_delivery'])
                ->count();
    
            if ($activeOrderCount >= 20) {
                throw new \Exception('Shipper đã đạt giới hạn đơn hàng tối đa (20 đơn)');
            }
    
            // Kiểm tra và thêm shipper vào bưu cục nếu cần  
            DB::table('post_office_shippers')->updateOrInsert(
                [
                    'post_office_id' => $postOffice->id,
                    'shipper_id' => $shipper->id
                ],
                [
                    'updated_at' => $currentTime
                ]
            );
    
            $assignedCount = 0;
            $failedOrders = [];
    
            foreach ($orders as $order) {
                try {
                    if ($order->canUpdateStatusTo(Order::STATUS_OUT_FOR_DELIVERY)) {
                        // Cập nhật trạng thái đơn hàng  
                        $order->update([
                            'status' => Order::STATUS_OUT_FOR_DELIVERY,
                            'assigned_shipper_id' => $shipper->id,
                            'assigned_at' => $currentTime
                        ]);
    
                        // Xóa bản ghi cũ trong order_distributions nếu có
                        DB::table('order_distributions')
                            ->where('order_id', $order->id)
                            ->delete();
    
                        // Thêm bản ghi mới vào order_distributions
                        DB::table('order_distributions')->insert([
                            'order_id' => $order->id,
                            'shipper_id' => $shipper->id,
                            'post_office_id' => $postOffice->id,
                            'distributed_by' => $user->id,
                            'distributed_at' => $currentTime,
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime
                        ]);
    
                        // Cập nhật order_location_histories
                        DB::table('order_location_histories')->insert([
                            'order_id' => $order->id,
                            'location_type' => PostOffice::class,
                            'location_id' => $postOffice->id,
                            'address' => $postOffice->address ?? '',
                            'coordinates' => $this->formatCoordinates($postOffice->coordinates),
                            'status' => Order::STATUS_OUT_FOR_DELIVERY,
                            'timestamp' => $currentTime,
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime
                        ]);
    
                        $assignedCount++;
    
                        Log::info('Đã phân công shipper thành công', [
                            'order_id' => $order->id,
                            'shipper_id' => $shipper->id,
                            'shipper_name' => $shipper->name
                        ]);
    
                    } else {
                        throw new \Exception('Không thể chuyển trạng thái đơn hàng sang giao hàng');
                    }
                } catch (\Exception $e) {
                    Log::error('Lỗi xử lý đơn hàng', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
    
                    $failedOrders[] = [
                        'id' => $order->id, 
                        'tracking_number' => $order->tracking_number,
                        'error' => $e->getMessage()
                    ];
                }
            }
    
            if ($assignedCount === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Không có đơn hàng nào được phân công thành công',
                    'failed_orders' => $failedOrders
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => "Đã phân công {$assignedCount} đơn hàng cho shipper {$shipper->name}",
                'assigned_count' => $assignedCount,
                'failed_orders' => $failedOrders
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi trong quá trình phân công:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() 
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    // Thêm phương thức mới để cập nhật trạng thái đang giao hàng
    public function startDelivery(Request $request)
    {
        try {
            Log::info('Bắt đầu cập nhật trạng thái giao hàng', [
                'request_data' => $request->all()
            ]);

            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'current_location' => 'required|array',
                'current_location.latitude' => 'required|numeric',
                'current_location.longitude' => 'required|numeric',
                'current_location.address' => 'required|string'
            ]);

            $user = Auth::user();
            $postOffice = $user->postOffices()->first();

            if (!$postOffice) {
                throw new \Exception('Bạn chưa được gán cho bất kỳ bưu cục nào');
            }

            DB::beginTransaction();

            $order = Order::where('id', $request->order_id)
                ->where('current_location_id', $postOffice->id)
                ->where('current_location_type', PostOffice::class)
                ->where('status', Order::STATUS_OUT_FOR_DELIVERY)
                ->firstOrFail();

            // Cập nhật trạng thái giao hàng
            DB::table('order_deliveries')
                ->where('order_id', $order->id)
                ->where('post_office_id', $postOffice->id)
                ->where('status', 'assigned')
                ->update([
                    'status' => 'in_delivery',
                    'started_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

            // Thêm vào lịch sử vị trí với vị trí hiện tại của shipper
            DB::table('order_location_histories')->insert([
                'order_id' => $order->id,
                'location_type' => 'shipper_location',
                'location_id' => $order->assigned_shipper_id,
                'address' => $request->current_location['address'],
                'coordinates' => json_encode([
                    $request->current_location['latitude'],
                    $request->current_location['longitude']
                ]),
                'status' => 'in_delivery',
                'timestamp' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            DB::commit();

            Log::info('Cập nhật trạng thái giao hàng thành công', [
                'order_id' => $order->id,
                'shipper_id' => $order->assigned_shipper_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái giao hàng thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật trạng thái giao hàng:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatCoordinates($coordinates)
    {
        if (empty($coordinates)) {
            return null;
        }

        if (is_string($coordinates)) {
            return $coordinates;
        }

        if (is_array($coordinates)) {
            return json_encode($coordinates);
        }

        return null;
    }

    public function getOrderDetails($orderId)
    {
        try {
            $user = Auth::user();
            $postOffice = $user->postOffices()->first();

            if (!$postOffice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gán cho bất kỳ bưu cục nào'
                ], 403);
            }

            $order = Order::with([
                'sender',
                'receiver',
                'currentPostOffice',
                'lastHandover',
                'lastHandover.originPostOffice',
                'lastHandover.distributionStaff',
                'statusHistories' => function($query) {
                    $query->latest()->take(5);
                }
            ])->findOrFail($orderId);

            // Kiểm tra quyền truy cập
            if ($order->current_location_id !== $postOffice->id || 
                $order->current_location_type !== PostOffice::class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem thông tin đơn hàng này'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin đơn hàng:', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getShipperDetails($shipperId)
    {
        try {
            $user = Auth::user();
            $postOffice = $user->postOffices()->first();

            if (!$postOffice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gán cho bất kỳ bưu cục nào'
                ], 403);
            }

            $shipper = Shipper::with([
                'activeOrders' => function($query) {
                    $query->whereIn('status', ['assigned', 'in_delivery'])
                        ->latest();
                },
                'completedOrders' => function($query) {
                    $query->where('status', 'delivered')
                        ->whereDate('completed_at', Carbon::today())
                        ->latest();
                }
            ])
            ->withCount([
                'activeOrders as pending_count' => function($query) {
                    $query->where('status', 'assigned');
                },
                'activeOrders as delivering_count' => function($query) {
                    $query->where('status', 'in_delivery');
                },
                'completedOrders as completed_today' => function($query) {
                    $query->whereDate('completed_at', Carbon::today());
                }
            ])
            ->findOrFail($shipperId);

            // Kiểm tra shipper có thuộc bưu cục không
            $isShipperInPostOffice = DB::table('post_office_shippers')
                ->where('post_office_id', $postOffice->id)
                ->where('shipper_id', $shipperId)
                ->exists();

            if (!$isShipperInPostOffice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipper không thuộc bưu cục của bạn'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'shipper' => $shipper,
                    'stats' => [
                        'pending_orders' => $shipper->pending_count,
                        'delivering_orders' => $shipper->delivering_count,
                        'completed_today' => $shipper->completed_today,
                        'total_active' => $shipper->activeOrders->count(),
                        'capacity_used' => ($shipper->activeOrders->count() / 20) * 100
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin shipper:', [
                'shipper_id' => $shipperId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPostOfficeStats()
    {
        try {
            $user = Auth::user();
            $postOffice = $user->postOffices()->first();

            if (!$postOffice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gán cho bất kỳ bưu cục nào'
                ], 403);
            }

            $today = Carbon::today();

            // Thống kê đơn hàng theo trạng thái
            $orderStats = Order::where('current_location_id', $postOffice->id)
                ->where('current_location_type', PostOffice::class)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            // Thống kê shipper
            $shipperStats = Shipper::whereHas('postOffices', function($query) use ($postOffice) {
                    $query->where('post_offices.id', $postOffice->id);
                })
                ->withCount([
                    'activeOrders as total_active',
                    'activeOrders as pending_count' => function($query) {
                        $query->where('status', 'assigned');
                    },
                    'completedOrders as completed_today' => function($query) use ($today) {
                        $query->whereDate('completed_at', $today);
                    }
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => [
                        'waiting_confirmation' => $orderStats[Order::STATUS_ARRIVED_WAITING_CONFIRMATION] ?? 0,
                        'confirmed' => $orderStats[Order::STATUS_CONFIRMED_AT_DESTINATION] ?? 0,
                        'out_for_delivery' => $orderStats[Order::STATUS_OUT_FOR_DELIVERY] ?? 0
                    ],
                    'shippers' => [
                        'total' => $shipperStats->count(),
                        'active' => $shipperStats->filter(fn($s) => $s->total_active > 0)->count(),
                        'available' => $shipperStats->filter(fn($s) => $s->total_active < 20)->count(),
                        'completed_today' => $shipperStats->sum('completed_today')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thống kê bưu cục:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAssignedOrders()
    {
        try {
            DB::enableQueryLog();
            
            $user = Auth::user();
            
            \Log::info('User info:', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);
    
            $postOffice = $user->postOffices()->first();
    
            \Log::info('Post Office info:', [
                'post_office' => $postOffice ? [
                    'id' => $postOffice->id,
                    'name' => $postOffice->name
                ] : null
            ]);
    
            if (!$postOffice) {
                \Log::error('User không có bưu cục:', [
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gán cho bất kỳ bưu cục nào'
                ], 403);
            }
    
            // Kiểm tra từng bảng riêng lẻ
            $orderDistributionsCount = DB::table('order_distributions')
                ->where('post_office_id', $postOffice->id)
                ->count();
                
            $shippersCount = DB::table('shippers')->count();
            $ordersCount = DB::table('orders')->count();
            $usersCount = DB::table('users')->count();
    
            \Log::info('Table counts:', [
                'order_distributions' => $orderDistributionsCount,
                'shippers' => $shippersCount,
                'orders' => $ordersCount,
                'users' => $usersCount
            ]);
    
            // Updated query to only select existing columns
            $query = DB::table('order_distributions as od')
                ->leftJoin('orders as o', 'od.order_id', '=', 'o.id')
                ->leftJoin('shippers as s', 'od.shipper_id', '=', 's.id')
                ->leftJoin('users as u', 'od.distributed_by', '=', 'u.id')
                ->select([
                    'od.id',
                    'od.order_id',
                    'od.shipper_id',
                    'od.distributed_at',
                    'o.tracking_number',
                    'o.status',
                    's.name as shipper_name',
                    's.phone as shipper_phone',
                    'u.name as distributor_name'
                ])
                ->where('od.post_office_id', $postOffice->id);
    
            // Log raw query
            \Log::info('Raw Query:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
    
            // Get data
            $assignedOrders = $query->orderBy('od.distributed_at', 'desc')
                ->paginate(10);
    
            // Log kết quả
            \Log::info('Query Result:', [
                'total' => $assignedOrders->total(),
                'per_page' => $assignedOrders->perPage(),
                'current_page' => $assignedOrders->currentPage(),
                'data' => $assignedOrders->items()
            ]);
    
            // Log chi tiết query đã chạy
            \Log::info('Executed SQL:', DB::getQueryLog());
    
            return response()->json([
                'success' => true,
                'data' => $assignedOrders,
                'debug_info' => [
                    'table_counts' => [
                        'order_distributions' => $orderDistributionsCount,
                        'shippers' => $shippersCount,
                        'orders' => $ordersCount,
                        'users' => $usersCount
                    ]
                ]
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Error getting assigned orders:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'queries' => DB::getQueryLog()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}