<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\DistributionHandover;
use App\Models\PostOffice;
use App\Models\ProvincialWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseDistributorOrdersController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            // Kiểm tra người dùng có phải là nhân viên phân phối kho không
            if (!$user->hasAnyRole(['warehouse_local_distributor', 'warehouse_remote_distributor'])) {
                return redirect()->route('dashboard')
                    ->with('error', 'Bạn không có quyền truy cập chức năng này');
            }

            // Lấy thông tin kho đang làm việc
            $warehouse = $user->warehouseUsers()
                ->where('is_active', true)
                ->whereNull('end_date')
                ->first()?->warehouse;

            if (!$warehouse) {
                return redirect()->route('dashboard')
                    ->with('error', 'Bạn không được gán cho kho nào.');
            }

            // Khởi tạo các biến mặc định
            $localOrders = collect();
            $postOffices = collect();
            $warehouses = collect();
            $remoteOrders = collect();

            // Lấy các đơn hàng được gán
            $assignedHandovers = DistributionHandover::with(['order', 'destinationPostOffice', 'destinationWarehouse'])
                ->where('distribution_staff_id', $user->id)
                ->whereIn('status', ['pending', 'in_transit'])
                ->get();

            // Phân loại đơn hàng dựa theo role
            if ($user->hasRole('warehouse_local_distributor')) {
                // Lấy danh sách bưu cục trong tỉnh
                $postOffices = PostOffice::where('province', $warehouse->province)
                    ->orderBy('name')
                    ->get();

                $localOrders = $assignedHandovers
                    ->where('shipping_type', 'noi_thanh')
                    ->filter(function ($handover) {
                        return $handover->order->current_coordinates
                            && $handover->order->receiver_coordinates;
                    })
                    ->groupBy(function ($handover) {
                        return $handover->order->receiver_district;
                    });
            }

            if ($user->hasRole('warehouse_remote_distributor')) {
                // Lấy danh sách kho tổng
                $warehouses = ProvincialWarehouse::where('id', '!=', $warehouse->id)
                    ->orderBy('name')
                    ->get()
                    ->groupBy('province');

                $remoteOrders = $assignedHandovers
                    ->where('shipping_type', 'ngoai_thanh')
                    ->groupBy(function ($handover) {
                        return $handover->order->receiver_province;
                    });
            }

            // Lấy đơn đã hoàn thành
            $completedHandovers = DistributionHandover::with(['order', 'destinationPostOffice', 'destinationWarehouse'])
                ->where('distribution_staff_id', $user->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', now())
                ->latest('completed_at')
                ->get()
                ->map(function ($handover) {
                    if (
                        $handover->shipping_type === 'noi_thanh' &&
                        $handover->order->current_coordinates &&
                        $handover->order->receiver_coordinates
                    ) {
                        $distance = $this->calculateDistance(
                            $handover->order->current_coordinates[1],
                            $handover->order->current_coordinates[0],
                            $handover->order->receiver_coordinates[1],
                            $handover->order->receiver_coordinates[0]
                        );
                        $handover->order->calculated_distance = $distance;
                    }
                    return $handover;
                })
                ->groupBy(function ($handover) {
                    return $handover->completed_at->format('H:i');
                });

            Log::info('Loaded distributor orders:', [
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'local_orders' => $localOrders->sum(fn($district) => $district->count()),
                'remote_orders' => $remoteOrders->sum(fn($province) => $province->count()),
                'completed_orders' => $completedHandovers->sum(fn($hour) => $hour->count())
            ]);

            return view('warehouse.distributor.orders.index', compact(
                'warehouse',
                'localOrders',
                'remoteOrders',
                'postOffices',
                'warehouses',
                'completedHandovers'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading distributor orders:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    // Cập nhật trạng thái chuyển đơn đến bưu cục đích
    public function updateLocalDelivery(Request $request)
    {
        try {
            $validated = $request->validate([
                'handover_ids' => 'required|array',
                'handover_ids.*' => 'exists:distribution_handovers,id',
                'post_office_id' => 'required|exists:post_offices,id'
            ]);
    
            $user = Auth::user();
            if (!$user->hasRole('warehouse_local_distributor')) {
                Log::warning('Unauthorized access to updateLocalDelivery', [
                    'user_id' => $user->id,
                    'role' => $user->roles->pluck('name')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện thao tác này'
                ], 403);
            }
    
            $postOffice = PostOffice::findOrFail($validated['post_office_id']);
            $currentTime = now();
    
            DB::beginTransaction();
    
            $successCount = 0;
            $failedHandovers = [];
    
            foreach ($validated['handover_ids'] as $handoverId) {
                try {
                    $handover = DistributionHandover::with('order')
                        ->where('distribution_staff_id', $user->id)
                        ->findOrFail($handoverId);
    
                    $district_similarity = 0;
                    $province_similarity = 0;
    
                    similar_text(
                        strtolower($handover->order->receiver_district),
                        strtolower($postOffice->district),
                        $district_similarity
                    );
                    similar_text(
                        strtolower($handover->order->receiver_province),
                        strtolower($postOffice->province),
                        $province_similarity
                    );
    
                    Log::info('Checking location similarity:', [
                        'handover_id' => $handoverId,
                        'order' => [
                            'district' => $handover->order->receiver_district,
                            'province' => $handover->order->receiver_province
                        ],
                        'post_office' => [
                            'district' => $postOffice->district,
                            'province' => $postOffice->province
                        ],
                        'similarity' => [
                            'district' => $district_similarity,
                            'province' => $province_similarity
                        ]
                    ]);
    
                    if ($district_similarity < 80 || $province_similarity < 80) {
                        throw new \Exception('Bưu cục không phù hợp với địa chỉ người nhận');
                    }
    
                    // Cập nhật handover
                    try {
                        $handover->update([
                            'status' => 'completed',
                            'destination_post_office_id' => $postOffice->id,
                            'completed_at' => $currentTime
                        ]);
                        Log::info('Updated handover status', ['handover_id' => $handoverId]);
                    } catch (\Exception $e) {
                        Log::error('Error updating handover:', [
                            'handover_id' => $handoverId,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
    
                    // Xử lý post_office_order
                    try {
                        $existingPostOfficeOrder = DB::table('post_office_orders')
                            ->where('order_id', $handover->order_id)
                            ->first();
    
                        if ($existingPostOfficeOrder) {
                            DB::table('post_office_orders')
                                ->where('order_id', $handover->order_id)
                                ->update([
                                    'post_office_id' => $postOffice->id,
                                    'updated_at' => $currentTime
                                ]);
                            Log::info('Updated existing post_office_order', [
                                'order_id' => $handover->order_id
                            ]);
                        } else {
                            DB::table('post_office_orders')->insert([
                                'post_office_id' => $postOffice->id,
                                'order_id' => $handover->order_id,
                                'created_at' => $currentTime,
                                'updated_at' => $currentTime
                            ]);
                            Log::info('Created new post_office_order', [
                                'order_id' => $handover->order_id
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error handling post_office_order:', [
                            'order_id' => $handover->order_id,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
    
                    // Cập nhật order
                    try {
                        $handover->order->update([
                            'status' => 'arrived_waiting_confirmation',
                            'current_location_id' => $postOffice->id,
                            'current_location_type' => PostOffice::class,
                            'current_coordinates' => $postOffice->coordinates,
                            'current_location' => $postOffice->address
                        ]);
                        Log::info('Updated order status', ['order_id' => $handover->order_id]);
                    } catch (\Exception $e) {
                        Log::error('Error updating order:', [
                            'order_id' => $handover->order_id,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
    
                    $successCount++;
    
                } catch (\Exception $e) {
                    Log::error('Failed to process handover:', [
                        'handover_id' => $handoverId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $failedHandovers[] = [
                        'id' => $handoverId,
                        'reason' => $e->getMessage()
                    ];
                }
            }
    
            if ($successCount === 0) {
                DB::rollBack();
                Log::warning('No orders were updated successfully', [
                    'failed_handovers' => $failedHandovers
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Không có đơn hàng nào được cập nhật thành công',
                    'failed_handovers' => $failedHandovers
                ], 422);
            }
    
            DB::commit();
            Log::info('Successfully updated local delivery', [
                'success_count' => $successCount,
                'post_office' => $postOffice->name
            ]);
    
            return response()->json([
                'success' => true,
                'message' => "Đã chuyển {$successCount} đơn hàng đến bưu cục {$postOffice->name}",
                'failed_handovers' => $failedHandovers
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Critical error in updateLocalDelivery:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật trạng thái chuyển đơn đến kho tổng đích
    public function updateRemoteDelivery(Request $request)
    {
        try {
            $validated = $request->validate([
                'handover_ids' => 'required|array',
                'handover_ids.*' => 'exists:distribution_handovers,id',
                'warehouse_id' => 'required|exists:provincial_warehouses,id'
            ]);

            $user = Auth::user();
            if (!$user->hasRole('warehouse_remote_distributor')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện thao tác này'
                ], 403);
            }

            $destinationWarehouse = ProvincialWarehouse::findOrFail($validated['warehouse_id']);
            $currentTime = now();

            DB::beginTransaction();

            $successCount = 0;
            $failedHandovers = [];

            foreach ($validated['handover_ids'] as $handoverId) {
                try {
                    $handover = DistributionHandover::with('order')
                        ->where('distribution_staff_id', $user->id)
                        ->findOrFail($handoverId);

                    // Kiểm tra tính hợp lệ
                    if ($handover->order->receiver_province !== $destinationWarehouse->province) {
                        throw new \Exception('Kho không phù hợp với tỉnh/thành người nhận');
                    }

                    // Cập nhật handover
                    $handover->update([
                        'status' => 'completed',
                        'destination_warehouse_id' => $destinationWarehouse->id,
                        'completed_at' => $currentTime
                    ]);

                    // Cập nhật order  
                    $handover->order->update([
                        'status' => 'arrived_at_warehouse',
                        'current_location_id' => $destinationWarehouse->id,
                        'current_location_type' => ProvincialWarehouse::class,
                        'current_coordinates' => $destinationWarehouse->coordinates,
                        'current_location' => $destinationWarehouse->address
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $failedHandovers[] = [
                        'id' => $handoverId,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            if ($successCount === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Không có đơn hàng nào được cập nhật thành công',
                    'failed_handovers' => $failedHandovers
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Đã chuyển {$successCount} đơn hàng đến kho {$destinationWarehouse->name}",
                'failed_handovers' => $failedHandovers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating remote delivery:', [
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
