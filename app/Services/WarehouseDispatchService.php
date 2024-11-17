<?php

namespace App\Services;

use App\Models\Order;
use App\Models\DistributionHandover;
use App\Models\ProvincialWarehouse;
use App\Models\PostOffice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WarehouseDispatchService
{
    /**
     * Xử lý điều phối đơn hàng đến kho tổng
     */
    public function dispatchToWarehouse(array $orderIds, int $warehouseId, int $staffId, PostOffice $currentPostOffice)
    {
        $results = [
            'dispatched' => 0,
            'failed' => [],
            'success' => false,
            'message' => ''
        ];

        try {
            DB::beginTransaction();

            $warehouse = ProvincialWarehouse::findOrFail($warehouseId);
            
            foreach ($orderIds as $orderId) {
                try {
                    $order = Order::findOrFail($orderId);
                    
                    if ($this->isEligibleForWarehouseDispatch($order, $warehouse)) {
                        // Tạo bản ghi distribution_handovers
                        $handover = new DistributionHandover([
                            'order_id' => $order->id,
                            'distribution_staff_id' => $staffId,
                            'origin_post_office_id' => $currentPostOffice->id,
                            'destination_post_office_id' => null,
                            'destination_warehouse_id' => $warehouse->id,
                            'shipping_type' => $order->shipping_type,
                            'status' => 'in_transit',
                            'distance' => $order->calculated_distance,
                            'assigned_at' => now(),
                        ]);

                        $handover->save();

                        // Cập nhật trạng thái đơn hàng
                        $order->update([
                            'status' => Order::STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE, 
                            'current_location_id' => $currentPostOffice->id,
                            'current_location_type' => PostOffice::class,
                        ]);

                        $results['dispatched']++;

                        Log::info('Đơn hàng đã được điều phối đến kho tổng', [
                            'order_id' => $order->id,
                            'warehouse_id' => $warehouse->id,
                            'staff_id' => $staffId
                        ]);
                    } else {
                        $results['failed'][] = [
                            'tracking_number' => $order->tracking_number,
                            'reason' => $this->getFailureReason($order, $warehouse)
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Lỗi xử lý đơn hàng riêng lẻ', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $results['failed'][] = [
                        'tracking_number' => $orderId,
                        'reason' => 'Lỗi xử lý: ' . $e->getMessage()
                    ];
                }
            }

            if ($results['dispatched'] > 0) {
                DB::commit();
                $results['success'] = true;
                $results['message'] = "Đã điều phối thành công {$results['dispatched']} đơn hàng đến kho tổng {$warehouse->name}";
            } else {
                DB::rollBack();
                $results['message'] = 'Không có đơn hàng nào được điều phối thành công';
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi trong quá trình điều phối đến kho tổng', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $results['message'] = 'Có lỗi xảy ra trong quá trình điều phối: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Kiểm tra điều kiện điều phối đến kho tổng
     */
    private function isEligibleForWarehouseDispatch(Order $order, ProvincialWarehouse $warehouse): bool
    {
        return ($order->shipping_type === 'ngoai_thanh' || 
                ($order->shipping_type === 'noi_thanh' && $order->calculated_distance > 20)) &&
               $order->sender_province === $warehouse->province;
    }

    /**
     * Lấy lý do thất bại khi không thể điều phối
     */
    private function getFailureReason(Order $order, ProvincialWarehouse $warehouse): string
    {
        $reasons = [];
        
        if ($order->shipping_type !== 'ngoai_thanh' && 
            ($order->shipping_type !== 'noi_thanh' || $order->calculated_distance <= 20)) {
            $reasons[] = 'Loại vận chuyển hoặc khoảng cách không phù hợp';
        }
        
        if ($order->sender_province !== $warehouse->province) {
            $reasons[] = 'Tỉnh gửi hàng không khớp với kho tổng';
        }
        
        return implode(', ', $reasons);
    }
}