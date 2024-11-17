<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Role;
use App\Models\PostOffice;
use App\Models\DistributionHandover;
use App\Models\ProvincialWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderDistributionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $assignedOrders = DistributionHandover::with(['order'])
            ->where('distribution_staff_id', $user->id)
            ->where('status', 'in_transit')
            ->paginate(10);

        return view('distribution.orders.index', compact('assignedOrders'));
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    private function findNearestPostOffice($latitude, $longitude)
    {
        return PostOffice::select(DB::raw("*, 
            ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) as distance"))
            ->setBindings([$longitude, $latitude])
            ->orderBy('distance')
            ->first();
    }

    public function assignDistribution(Request $request)
    {
        Log::info('Starting distribution assignment', ['request' => $request->all()]);

        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'shipper_id' => 'required|exists:users,id',
            ]);

            $order = Order::findOrFail($request->order_id);
            $shipper = User::findOrFail($request->shipper_id);
            $user = Auth::user();

            if (!$user->hasRole(['local_distribution_staff', 'general_distribution_staff'])) {
                return response()->json(['message' => 'Bạn không có quyền phân phối đơn hàng'], 403);
            }

            // Xác định bưu cục đầu và cuối dựa trên địa chỉ gửi và nhận
            $originPostOffice = $this->findNearestPostOffice(
                $order->sender_latitude,
                $order->sender_longitude
            );

            $destinationPostOffice = $this->findNearestPostOffice(
                $order->receiver_latitude,
                $order->receiver_longitude
            );

            if (!$originPostOffice || !$destinationPostOffice) {
                throw new \Exception('Không thể xác định bưu cục đầu hoặc cuối');
            }

            // Tính khoảng cách vận chuyển
            $distance = $this->calculateDistance(
                $originPostOffice->latitude,
                $originPostOffice->longitude,
                $destinationPostOffice->latitude,
                $destinationPostOffice->longitude
            );

            DB::beginTransaction();

            // Xác định điểm đến (bưu cục hoặc kho tổng) dựa trên loại vận chuyển và khoảng cách
            $destinationId = null;
            $destinationType = null;
            $shippingType = $order->shipping_type;

            if ($shippingType === 'noi_thanh' && $distance <= 20) {
                $destinationId = $destinationPostOffice->id;
                $destinationType = PostOffice::class;
            } else {
                // Tìm kho tổng phù hợp cho vận chuyển xa
                $destinationWarehouse = ProvincialWarehouse::where('province', $order->receiver_province)->first();
                if (!$destinationWarehouse) {
                    throw new \Exception('Không tìm thấy kho tổng phù hợp');
                }
                $destinationId = $destinationWarehouse->id;
                $destinationType = ProvincialWarehouse::class;
            }

            // Tạo bản ghi phân phối
            $handover = DistributionHandover::create([
                'order_id' => $order->id,
                'distribution_staff_id' => $shipper->id,
                'origin_post_office_id' => $originPostOffice->id,
                'shipping_type' => $shippingType,
                'distance' => $distance,
                'destination_id' => $destinationId,
                'destination_type' => $destinationType,
                'distributed_by' => $user->id,
                'distributed_at' => now(),
                'status' => 'in_transit'
            ]);

            // Cập nhật trạng thái đơn hàng
            $order->update([
                'status' => 'in_distribution',
                'current_location_id' => $originPostOffice->id,
                'current_location_type' => PostOffice::class
            ]);

            DB::commit();

            Log::info('Distribution assignment completed successfully', [
                'handover_id' => $handover->id,
                'order_id' => $order->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã phân phối đơn hàng thành công',
                'data' => $handover
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in distribution assignment:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableShippers(Request $request)
    {
        try {
            $request->validate([
                'post_office_id' => 'required|exists:post_offices,id',
            ]);

            $postOffice = PostOffice::findOrFail($request->post_office_id);

            $availableShippers = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['local_distribution_staff', 'general_distribution_staff']);
                })
                ->whereHas('postOffices', function ($query) use ($postOffice) {
                    $query->where('post_offices.id', $postOffice->id);
                })
                ->withCount(['activeDistributions' => function ($query) {
                    $query->where('status', 'in_transit');
                }])
                ->having('active_distributions_count', '<', 20)
                ->get(['id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'data' => $availableShippers
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available shippers:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function batchUpdate(Request $request)
    {
        try {
            Log::info('Bắt đầu cập nhật hàng loạt đơn hàng', [
                'request_data' => $request->all()
            ]);
    
            $request->validate([
                'handover_ids' => 'required|array',
                'handover_ids.*' => 'required|exists:distribution_handovers,id'
            ]);
    
            $user = Auth::user();
            $isGeneralStaff = $user->hasRole('general_distribution_staff');
            
            DB::beginTransaction();
    
            $handovers = DistributionHandover::with(['order', 'destinationWarehouse'])
                ->whereIn('id', $request->handover_ids)
                ->where('distribution_staff_id', $user->id)
                ->where('status', 'in_transit')
                ->get();
    
            if ($handovers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng cần cập nhật.'
                ]);
            }
    
            $updatedCount = 0;
            $failedHandovers = [];
    
            foreach ($handovers as $handover) {
                try {
                    Log::info('Đang cập nhật đơn hàng', [
                        'handover_id' => $handover->id,
                        'order_id' => $handover->order_id,
                        'is_general_staff' => $isGeneralStaff
                    ]);
    
                    // Cập nhật trạng thái handover
                    $handover->update([
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);
    
                    $order = $handover->order;
                    if ($order) {
                        if ($isGeneralStaff && $handover->destination_warehouse_id) {
                            // Xử lý cho nhân viên phân phối chung - cập nhật warehouse_orders
                            DB::table('warehouse_orders')->insert([
                                'order_id' => $order->id,
                                'provincial_warehouse_id' => $handover->destination_warehouse_id,
                                'entered_at' => now(),
                                'status' => 'entered',
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
    
                            $warehouse = $handover->destinationWarehouse;
                            if ($warehouse) {
                                $order->update([
                                    'status' => Order::STATUS_ARRIVED_AT_WAREHOUSE,
                                    'current_location_id' => $warehouse->id,
                                    'current_location_type' => ProvincialWarehouse::class,
                                    'current_coordinates' => $warehouse->coordinates,
                                    'current_location' => $warehouse->address
                                ]);
                            }
                        } else {
                            // Xử lý cho nhân viên phân phối nội bộ - giữ nguyên logic cũ
                            $destinationPostOffice = PostOffice::find($handover->destination_post_office_id);
                            if ($destinationPostOffice) {
                                $order->update([
                                    'status' => Order::STATUS_ARRIVED_WAITING_CONFIRMATION,
                                    'current_location_id' => $destinationPostOffice->id,
                                    'current_location_type' => PostOffice::class,
                                    'current_coordinates' => $destinationPostOffice->coordinates,
                                    'current_location' => $destinationPostOffice->address
                                ]);
    
                                DB::table('post_office_orders')->updateOrInsert(
                                    ['order_id' => $order->id],
                                    [
                                        'post_office_id' => $destinationPostOffice->id,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]
                                );
                            }
                        }
    
                        Log::info('Đã cập nhật thành công đơn hàng', [
                            'handover_id' => $handover->id,
                            'order_tracking' => $order->tracking_number,
                            'new_status' => $order->status
                        ]);
    
                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Lỗi khi cập nhật đơn hàng', [
                        'handover_id' => $handover->id,
                        'error' => $e->getMessage()
                    ]);
    
                    $failedHandovers[] = [
                        'id' => $handover->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
    
            if ($updatedCount > 0) {
                DB::commit();
                $message = "Đã cập nhật thành công {$updatedCount} đơn hàng.";
                if (!empty($failedHandovers)) {
                    $message .= " Có " . count($failedHandovers) . " đơn hàng cập nhật thất bại.";
                }
    
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'updated_count' => $updatedCount,
                    'failed_handovers' => $failedHandovers
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Không có đơn hàng nào được cập nhật thành công.',
                    'failed_handovers' => $failedHandovers
                ]);
            }
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi trong quá trình cập nhật hàng loạt:', [
                'error' => $e->getMessage()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}