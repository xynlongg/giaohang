<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PostOffice;
use App\Models\ProvincialWarehouse; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributionStaffController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $perPage = 10;
        $currentPage = request()->get('page', 1);
    
        if ($user->hasRole('local_distribution_staff')) {
            $postOffice = $user->postOffices()->first();
            if (!$postOffice) {
                return redirect()->route('dashboard')->with('error', 'Bạn chưa được gán cho bất kỳ bưu cục nào.');
            }
    
            // Lấy tất cả đơn hàng phù hợp
            $allOrders = Order::whereHas('postOffices', function ($query) use ($postOffice) {
                $query->where('post_offices.id', $postOffice->id);
            })
            ->where(function ($query) {
                $query->where('shipping_type', 'cung_quan')
                    ->orWhere('shipping_type', 'noi_thanh');
            })
            ->with(['postOffices'])
            ->get()
            ->filter(function ($order) {
                if ($order->shipping_type === 'noi_thanh') {
                    return $order->calculated_distance <= 20;
                }
                return true;
            });
    
            // Tạo paginator từ collection đã lọc
            $assignedOrders = new \Illuminate\Pagination\LengthAwarePaginator(
                $allOrders->forPage($currentPage, $perPage),
                $allOrders->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
    
        } elseif ($user->hasRole('general_distribution_staff')) {
            $warehouseStaff = $user->warehouseStaff;
            if (!$warehouseStaff || !$warehouseStaff->provincialWarehouse) {
                return redirect()->route('dashboard')->with('error', 'Bạn chưa được gán cho bất kỳ kho tổng nào.');
            }
    
            $warehouse = $warehouseStaff->provincialWarehouse;
    
            // Lấy tất cả đơn hàng phù hợp
            $allOrders = Order::whereHas('warehouseOrders', function ($query) use ($warehouse) {
                $query->where('provincial_warehouse_id', $warehouse->id)
                    ->where('status', 'entered');
            })
            ->where(function ($query) {
                $query->where('shipping_type', 'ngoai_thanh')
                    ->orWhere('shipping_type', 'noi_thanh');
            })
            ->with(['warehouseOrders'])
            ->get()
            ->filter(function ($order) {
                if ($order->shipping_type === 'noi_thanh') {
                    return $order->calculated_distance > 20;
                }
                return true;
            });
    
            // Tạo paginator từ collection đã lọc
            $assignedOrders = new \Illuminate\Pagination\LengthAwarePaginator(
                $allOrders->forPage($currentPage, $perPage),
                $allOrders->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
    
        } else {
            // Empty paginator for users without proper roles
            $assignedOrders = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }
    
        return view('distribution.orders.index', compact('assignedOrders'));
    }

    public function updateOrderArrival(Request $request, Order $order)
    {
        Log::info('Starting updateOrderArrival', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);
    
        try {
            $user = Auth::user();
            DB::beginTransaction();
    
            Log::info('User role check', [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name'),
                'is_local_staff' => $user->hasRole('local_distribution_staff'),
                'is_general_staff' => $user->hasRole('general_distribution_staff')
            ]);
    
            if ($user->hasRole('local_distribution_staff')) {
                $postOffice = $user->postOffices()->first();
                Log::info('Local distribution staff processing', [
                    'post_office' => $postOffice ? $postOffice->toArray() : null
                ]);
    
                if (!$postOffice) {
                    throw new \Exception('Bạn chưa được gán cho bất kỳ bưu cục nào.');
                }
    
                // Kiểm tra xem đơn hàng có thuộc về bưu cục không
                $orderBelongsToPostOffice = DB::table('post_office_orders')
                    ->where('order_id', $order->id)
                    ->where('post_office_id', $postOffice->id)
                    ->exists();
    
                Log::info('Order belongs to post office check', [
                    'order_id' => $order->id,
                    'post_office_id' => $postOffice->id,
                    'belongs_to_post_office' => $orderBelongsToPostOffice
                ]);
    
                if (!$orderBelongsToPostOffice) {
                    throw new \Exception('Đơn hàng không thuộc về bưu cục của bạn.');
                }
    
                // Kiểm tra chuyển đổi trạng thái
                Log::info('Checking status transition', [
                    'current_status' => $order->status,
                    'new_status' => Order::STATUS_ARRIVED_AT_POST_OFFICE,
                    'can_update' => $order->canUpdateStatusTo(Order::STATUS_ARRIVED_AT_POST_OFFICE)
                ]);
    
                if (!$order->canUpdateStatusTo(Order::STATUS_ARRIVED_AT_POST_OFFICE)) {
                    throw new \Exception('Không thể cập nhật trạng thái đơn hàng thành "Đã đến bưu cục"');
                }
    
                // Cập nhật trạng thái và vị trí đơn hàng
                $updateData = [
                    'status' => Order::STATUS_ARRIVED_AT_POST_OFFICE,
                    'current_location_id' => $postOffice->id,
                    'current_location_type' => PostOffice::class,
                    'current_coordinates' => [$postOffice->longitude, $postOffice->latitude]
                ];
    
                Log::info('Updating order with data', [
                    'order_id' => $order->id,
                    'update_data' => $updateData
                ]);
    
                $order->update($updateData);
    
                // Cập nhật bảng post_office_orders
                DB::table('post_office_orders')
                    ->where('order_id', $order->id)
                    ->where('post_office_id', $postOffice->id)
                    ->update([
                        'updated_at' => now()
                    ]);
    
                $message = "Đơn hàng đã đến bưu cục " . $postOffice->name;
    
            } elseif ($user->hasRole('general_distribution_staff')) {
                $warehouseStaff = $user->warehouseStaff;
                
                Log::info('General distribution staff processing', [
                    'warehouse_staff' => $warehouseStaff ? $warehouseStaff->toArray() : null
                ]);
    
                if (!$warehouseStaff || !$warehouseStaff->provincialWarehouse) {
                    throw new \Exception('Bạn chưa được gán cho bất kỳ kho tổng nào.');
                }
    
                $warehouse = $warehouseStaff->provincialWarehouse;
    
                // Kiểm tra xem đơn hàng có thuộc về kho tổng không
                $orderBelongsToWarehouse = DB::table('warehouse_orders')
                    ->where('order_id', $order->id)
                    ->where('provincial_warehouse_id', $warehouse->id)
                    ->exists();
    
                Log::info('Order belongs to warehouse check', [
                    'order_id' => $order->id,
                    'warehouse_id' => $warehouse->id,
                    'belongs_to_warehouse' => $orderBelongsToWarehouse
                ]);
    
                if (!$orderBelongsToWarehouse) {
                    throw new \Exception('Đơn hàng không thuộc về kho tổng của bạn.');
                }
    
                // Cập nhật trạng thái và vị trí đơn hàng
                $updateData = [
                    'status' => Order::STATUS_ARRIVED_AT_WAREHOUSE,
                    'current_location_id' => $warehouse->id,
                    'current_location_type' => ProvincialWarehouse::class,
                    'current_coordinates' => [$warehouse->longitude, $warehouse->latitude]
                ];
    
                Log::info('Updating order with data', [
                    'order_id' => $order->id,
                    'update_data' => $updateData
                ]);
    
                $order->update($updateData);
    
                // Cập nhật bảng warehouse_orders
                DB::table('warehouse_orders')
                    ->where('order_id', $order->id)
                    ->where('provincial_warehouse_id', $warehouse->id)
                    ->update([
                        'status' => 'arrived',
                        'entered_at' => now(),
                        'updated_at' => now()
                    ]);
    
                $message = "Đơn hàng đã đến kho tổng " . $warehouse->name;
            }
    
            DB::commit();
    
            Log::info('Order arrival updated successfully', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'message' => $message
            ]);
    
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating order arrival', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 422);
        }
    }
    
    public function batchUpdateArrival(Request $request)
    {
        Log::info('Starting batch update arrival', [
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);
    
        try {
            $orderIds = $request->input('order_ids', []);
            
            if (empty($orderIds)) {
                throw new \Exception('Không có đơn hàng nào được chọn.');
            }
    
            Log::info('Processing batch update for orders', [
                'order_ids' => $orderIds
            ]);
    
            DB::beginTransaction();
    
            $successCount = 0;
            $failedOrders = [];
    
            foreach ($orderIds as $orderId) {
                try {
                    $order = Order::findOrFail($orderId);
                    
                    Log::info('Processing individual order in batch', [
                        'order_id' => $orderId,
                        'current_status' => $order->status
                    ]);
    
                    // Tạo request mới cho mỗi đơn hàng
                    $singleRequest = new Request();
                    $this->updateOrderArrival($singleRequest, $order);
                    
                    $successCount++;
    
                } catch (\Exception $e) {
                    Log::error('Error processing order in batch', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
    
                    $failedOrders[] = [
                        'id' => $orderId,
                        'error' => $e->getMessage()
                    ];
                }
            }
    
            DB::commit();
    
            $message = "Cập nhật thành công $successCount đơn hàng.";
            if (count($failedOrders) > 0) {
                $message .= " {count($failedOrders)} đơn hàng thất bại.";
            }
    
            Log::info('Batch update completed', [
                'success_count' => $successCount,
                'failed_orders' => $failedOrders
            ]);
    
            return response()->json([
                'success' => true,
                'message' => $message,
                'failed_orders' => $failedOrders
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error in batch update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 422);
        }
    }
}