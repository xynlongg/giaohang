<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderHandler;
use App\Models\WarehouseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\DistributionHandover;

class WarehouseOrderManagementController extends Controller
{
    const STATUS_ENTERED = 'entered';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_IN_DELIVERY = 'in_delivery';

    // Trong WarehouseOrderManagementController

    public function index()
    {
        try {
            $user = Auth::user();
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse) {
                return redirect()->route('dashboard')
                    ->with('error', 'Bạn không có quyền truy cập vào quản lý đơn hàng kho.');
            }

            // Lấy danh sách nhân viên phân phối
            $allDistributors = User::whereHas('warehouseUsers', function ($query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->whereNull('end_date');
            })
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['warehouse_local_distributor', 'warehouse_remote_distributor']);
                })
                ->get();

            // Phân loại nhân viên theo role
            $localDistributors = User::whereHas('warehouseUsers', function ($query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->whereNull('end_date');
            })
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'warehouse_local_distributor');
                })
                ->get();

            $nonLocalDistributors = User::whereHas('warehouseUsers', function ($query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->whereNull('end_date');
            })
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'warehouse_remote_distributor');
                })
                ->get();

            // Lấy tất cả đơn hàng hoàn thành ở kho với eager loading order
            $warehouseOrders = WarehouseOrder::with(['order'])
                ->where('provincial_warehouse_id', $warehouse->id)
                ->where('status', self::STATUS_COMPLETED)
                ->get();

            // Phân loại đơn hàng dựa trên shipping_type từ order chính
            $localOrders = $warehouseOrders->filter(function ($warehouseOrder) {
                return $warehouseOrder->order->shipping_type === 'noi_thanh';
            })->values();

            $nonLocalOrders = $warehouseOrders->filter(function ($warehouseOrder) {
                return $warehouseOrder->order->shipping_type === 'ngoai_thanh';
            })->values();

            // Lấy số đơn hiện tại của mỗi nhân viên
            $distributorOrderCounts = OrderHandler::where('warehouse_id', $warehouse->id)
                ->where('action_type', 'distribute')
                ->whereDate('created_at', now())
                ->groupBy('user_id')
                ->select('user_id', DB::raw('count(*) as order_count'))
                ->pluck('order_count', 'user_id')
                ->toArray();

            // Lấy danh sách đơn mới đến
            $newArrivals = WarehouseOrder::with(['order'])
                ->where('provincial_warehouse_id', $warehouse->id)
                ->where('status', self::STATUS_ENTERED)
                ->orderByDesc('entered_at')
                ->get();

            return view('warehouse.orders.index', compact(
                'warehouse',
                'warehouseOrders',
                'localOrders',
                'nonLocalOrders',
                'localDistributors',
                'nonLocalDistributors',
                'distributorOrderCounts',
                'newArrivals'
            ));
        } catch (\Exception $e) {
            Log::error('Error in warehouse orders index:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Có lỗi xảy ra khi tải danh sách đơn hàng: ' . $e->getMessage());
        }
    }

    public function assignDistributor(Request $request)
    {
        try {
            $user = Auth::user();
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện thao tác này.'
                ], 403);
            }

            // Validate đầu vào
            $validated = $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'exists:warehouse_orders,id',
                'distributor_id' => 'required|exists:users,id',
                'distribution_type' => 'required|in:local,non-local'
            ]);

            // Kiểm tra giới hạn số đơn
            $distributorOrderCount = OrderHandler::where('user_id', $validated['distributor_id'])
                ->where('warehouse_id', $warehouse->id)
                ->where('action_type', 'distribute')
                ->whereDate('created_at', now())
                ->count();

            if ($distributorOrderCount + count($validated['order_ids']) > 20) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nhân viên đã đạt giới hạn số đơn trong ngày (tối đa 20 đơn/ngày).'
                ], 400);
            }

            // Kiểm tra nhân viên
            $distributor = User::whereHas('warehouseUsers', function ($query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('is_active', true)
                    ->whereNull('end_date');
            })
                ->whereHas('roles', function ($query) use ($validated) {
                    $query->where('name', $validated['distribution_type'] === 'local' ?
                        'warehouse_local_distributor' : 'warehouse_remote_distributor');
                })
                ->find($validated['distributor_id']);

            if (!$distributor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nhân viên không hợp lệ.'
                ], 400);
            }

            // Kiểm tra đơn hàng
            $orders = WarehouseOrder::with('order')
                ->whereIn('id', $validated['order_ids'])
                ->get();

            foreach ($orders as $order) {
                // Kiểm tra theo shipping_type thay vì province
                $isLocalOrder = $order->order->shipping_type === 'noi_thanh';

                if (($isLocalOrder && $validated['distribution_type'] !== 'local') ||
                    (!$isLocalOrder && $validated['distribution_type'] !== 'non-local')
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Có đơn hàng không phù hợp với loại phân phối.'
                    ], 400);
                }

                if ($order->status !== self::STATUS_COMPLETED) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ được gán đơn hàng đã hoàn thành xác nhận.'
                    ], 400);
                }
            }

            DB::beginTransaction();
            try {
                foreach ($validated['order_ids'] as $orderId) {
                    $order = $orders->firstWhere('id', $orderId);

                    // Kiểm tra bản ghi đã tồn tại
                    $handover = DistributionHandover::where('order_id', $order->order_id)
                        ->where('status', '!=', 'completed')
                        ->first();

                    if ($handover) {
                        // Update bản ghi cũ
                        $handover->update([
                            'distribution_staff_id' => $validated['distributor_id'],
                            'destination_warehouse_id' => $warehouse->id,
                            'shipping_type' => $validated['distribution_type'] === 'local' ? 'noi_thanh' : 'ngoai_thanh',
                            'status' => 'pending',
                            'destination_post_office_id' => null,
                            'origin_post_office_id' => null,
                            'distance' => 0,
                            'assigned_at' => now()
                        ]);
                    } else {
                        // Tạo bản ghi mới
                        DistributionHandover::create([
                            'order_id' => $order->order_id,
                            'distribution_staff_id' => $validated['distributor_id'],
                            'destination_warehouse_id' => $warehouse->id,
                            'shipping_type' => $validated['distribution_type'] === 'local' ? 'noi_thanh' : 'ngoai_thanh',
                            'status' => 'pending',
                            'destination_post_office_id' => null,
                            'origin_post_office_id' => null,
                            'distance' => 0,
                            'assigned_at' => now()
                        ]);
                    }

                    // Cập nhật trạng thái đơn kho
                    $order->update([
                        'status' => self::STATUS_IN_DELIVERY
                    ]);

                    // Cập nhật trạng thái đơn hàng chính
                    $order->order->update([
                        'status' => 'in_delivery',
                        'current_location_id' => $warehouse->id,
                        'current_location_type' => get_class($warehouse),
                        'current_coordinates' => $warehouse->coordinates,
                        'current_location' => $warehouse->address
                    ]);

                    // Ghi log hành động
                    OrderHandler::create([
                        'order_id' => $order->order_id,
                        'user_id' => $validated['distributor_id'],
                        'warehouse_id' => $warehouse->id,
                        'action_type' => 'distribute',
                        'distribution_type' => $validated['distribution_type'],
                        'notes' => sprintf(
                            'Gán cho nhân viên phân phối %s %s bởi %s',
                            $validated['distribution_type'] === 'local' ? 'nội thành' : 'ngoại thành',
                            $distributor->name,
                            $user->name
                        )
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => sprintf(
                        'Đã gán %d đơn hàng cho nhân viên %s %s thành công.',
                        count($validated['order_ids']),
                        $distributor->name,
                        $validated['distribution_type'] === 'local' ? '(nội thành)' : '(ngoại thành)'
                    )
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error assigning distributor:', [
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
            $user = Auth::user();
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập.'
                ], 403);
            }

            $assignedOrders = OrderHandler::with(['order', 'user'])
                ->where('warehouse_id', $warehouse->id)
                ->where('action_type', 'distribute')
                ->whereDate('created_at', now())
                ->latest()
                ->get()
                ->map(function ($handler) {
                    $order = $handler->order;
                    return [
                        'id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'receiver_name' => $order->receiver_name,
                        'receiver_phone' => $order->receiver_phone,
                        'receiver_address' => $order->receiver_address,
                        'receiver_district' => $order->receiver_district,
                        'receiver_province' => $order->receiver_province,
                        'shipping_type' => $order->shipping_type,
                        'pickup_location_id' => $order->pickup_location_id,
                        'current_location' => $order->current_location,
                        'total_weight' => $order->total_weight,
                        'total_cod' => $order->total_cod,
                        'status' => $order->status,
                        'assigned_at' => $handler->created_at->format('Y-m-d H:i:s'),
                        'distributor_name' => $handler->user->name,
                        'distributor_phone' => $handler->user->phone
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $assignedOrders
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting assigned orders:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $warehouseOrder = WarehouseOrder::findOrFail($id);
            $user = Auth::user();
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse || $warehouse->id !== $warehouseOrder->provincial_warehouse_id) {
                return redirect()->route('warehouse.orders.index')
                    ->with('error', 'Bạn không có quyền xem đơn hàng này.');
            }

            return view('warehouse.orders.show', compact('warehouseOrder'));
        } catch (\Exception $e) {
            Log::error('Error showing warehouse order:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('warehouse.orders.index')
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if (!$request->ajax()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Yêu cầu không hợp lệ.'
                    ], 400);
                }
                return redirect()->route('warehouse.orders.index');
            }

            $warehouseOrder = WarehouseOrder::findOrFail($id);
            $user = Auth::user();
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse || $warehouse->id !== $warehouseOrder->provincial_warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật đơn hàng này.'
                ], 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:' . self::STATUS_PROCESSING . ',' . self::STATUS_COMPLETED
            ]);

            DB::beginTransaction();
            try {
                // Update status cho warehouse order
                $warehouseOrder->update([
                    'status' => $validated['status'],
                    $validated['status'] . '_at' => now()
                ]);

                // Update order status nếu hoàn thành
                if ($validated['status'] === self::STATUS_COMPLETED) {
                    $warehouseOrder->order->update([
                        'status' => 'arrived_at_warehouse',
                        'current_location_id' => $warehouse->id,
                        'current_location_type' => get_class($warehouse),
                        'current_coordinates' => $warehouse->coordinates,
                        'current_location' => $warehouse->address
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật trạng thái đơn hàng thành công.'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error updating warehouse order:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmArrival(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện thao tác này.'
                ], 403);
            }

            DB::beginTransaction();
            try {
                $warehouseOrder = WarehouseOrder::with('order')->findOrFail($id);

                if ($warehouseOrder->provincial_warehouse_id !== $warehouse->id) {
                    throw new \Exception('Đơn hàng không thuộc kho này');
                }

                // Cập nhật trạng thái đơn kho
                $warehouseOrder->update([
                    'status' => self::STATUS_COMPLETED,
                    'completed_at' => now()
                ]);

                // Cập nhật thông tin vị trí đơn hàng chính
                $warehouseOrder->order->update([
                    'status' => 'arrived_at_warehouse',
                    'current_location_id' => $warehouse->id,
                    'current_location_type' => get_class($warehouse),
                    'current_coordinates' => $warehouse->coordinates,
                    'current_location' => $warehouse->address
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Đã xác nhận đơn hàng đến kho thành công.'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error confirming warehouse arrival:', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmBulkArrival(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'exists:warehouse_orders,id'
            ]);

            $user = Auth::user();
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện thao tác này.'
                ], 403);
            }

            DB::beginTransaction();
            try {
                foreach ($validated['order_ids'] as $id) {
                    $warehouseOrder = WarehouseOrder::with('order')->findOrFail($id);

                    if ($warehouseOrder->provincial_warehouse_id !== $warehouse->id) {
                        throw new \Exception('Có đơn hàng không thuộc kho này');
                    }

                    // Cập nhật trạng thái đơn kho
                    $warehouseOrder->update([
                        'status' => self::STATUS_COMPLETED,
                        'completed_at' => now()
                    ]);

                    // Cập nhật thông tin vị trí đơn hàng chính
                    $warehouseOrder->order->update([
                        'status' => 'arrived_at_warehouse',
                        'current_location_id' => $warehouse->id,
                        'current_location_type' => get_class($warehouse),
                        'current_coordinates' => $warehouse->coordinates,
                        'current_location' => $warehouse->address
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Đã xác nhận ' . count($validated['order_ids']) . ' đơn hàng đến kho thành công.'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error confirming bulk warehouse arrival:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
