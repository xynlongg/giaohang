<?php

namespace App\Http\Controllers;

use App\Models\DistributionHandover;
use App\Models\Order;
use App\Models\PostOffice;
use App\Models\ProvincialWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributorAssignedOrdersController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            $currentPostOffice = $user->postOffices()->first();

            if (!$currentPostOffice) {
                return redirect()->route('dashboard')
                    ->with('error', 'Bạn chưa được gán cho bất kỳ bưu cục nào.');
            }

            // Lấy các đơn đang xử lý của nhân viên 
            $assignedHandovers = DistributionHandover::with(['order', 'destinationPostOffice', 'destinationWarehouse'])
                ->where('distribution_staff_id', $user->id)
                ->where('origin_post_office_id', $currentPostOffice->id)
                ->whereIn('status', ['in_transit', 'pending'])
                ->get();

            // Lấy danh sách kho tổng trong cùng tỉnh với bưu cục hiện tại
            $warehouses = ProvincialWarehouse::where('province', $currentPostOffice->province)
                ->orderBy('name')
                ->get();

            // Phân loại đơn cần chuyển kho (gộp đơn ngoại thành và nội thành > 20km)
            $warehouseOrders = collect();
            
            // Lọc đơn nội thành > 20km
            $longDistanceLocalOrders = $assignedHandovers
                ->where('shipping_type', 'noi_thanh')
                ->filter(function ($handover) {
                    if ($handover->order->current_coordinates && $handover->order->receiver_coordinates) {
                        $distance = $this->calculateDistance(
                            $handover->order->current_coordinates[1],
                            $handover->order->current_coordinates[0], 
                            $handover->order->receiver_coordinates[1],
                            $handover->order->receiver_coordinates[0]
                        );
                        $handover->order->calculated_distance = $distance;
                        return $distance > 20;
                    }
                    return false;
                });

            // Lọc đơn ngoại thành
            $nonLocalOrders = $assignedHandovers->where('shipping_type', 'ngoai_thanh');

            // Gom 2 loại đơn vào chung một collection
            $warehouseOrders = $longDistanceLocalOrders->concat($nonLocalOrders)
                ->groupBy(function ($handover) {
                    return $handover->order->receiver_province;
                });

            // Phân loại đơn nội thành <= 20km
            $localOrders = $assignedHandovers
                ->where('shipping_type', 'noi_thanh')
                ->filter(function ($handover) {
                    if ($handover->order->current_coordinates && $handover->order->receiver_coordinates) {
                        $distance = $this->calculateDistance(
                            $handover->order->current_coordinates[1],
                            $handover->order->current_coordinates[0],
                            $handover->order->receiver_coordinates[1],
                            $handover->order->receiver_coordinates[0]
                        );
                        $handover->order->calculated_distance = $distance; 
                        return $distance <= 20;
                    }
                    return false;
                })
                ->groupBy(function ($handover) {
                    return $handover->order->receiver_district;
                });

            // Lấy đơn đã hoàn thành 24h qua
            $recentlyCompletedHandovers = DistributionHandover::with(['order', 'destinationPostOffice', 'destinationWarehouse'])
                ->where('distribution_staff_id', $user->id)
                ->where('origin_post_office_id', $currentPostOffice->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subHours(24))
                ->orderBy('completed_at', 'desc')
                ->get()
                ->map(function ($handover) {
                    if ($handover->order->current_coordinates && $handover->order->receiver_coordinates) {
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
                    return $handover->completed_at->format('Y-m-d H');
                });

            // Lấy danh sách bưu cục cho đơn nội thành <= 20km
            $postOffices = collect();
            if ($localOrders->isNotEmpty()) {
                $postOffices = PostOffice::whereIn('district', $localOrders->keys())
                    ->where('province', $currentPostOffice->province)
                    ->orderBy('name')
                    ->get();
            }

            // Log thông tin chi tiết
            Log::info('Chi tiết phân loại đơn hàng:', [
                'user_id' => $user->id,
                'bưu_cục' => [
                    'id' => $currentPostOffice->id,
                    'tên' => $currentPostOffice->name,
                    'tỉnh' => $currentPostOffice->province
                ],
                'thống_kê' => [
                    'tổng_đơn_đang_xử_lý' => $assignedHandovers->count(),
                    'đơn_nội_thành_dưới_20km' => $localOrders->sum(fn($district) => $district->count()),
                    'đơn_cần_chuyển_kho' => $warehouseOrders->sum(fn($province) => $province->count()),
                    'kho_khả_dụng' => $warehouses->count(),
                    'đơn_hoàn_thành_24h' => $recentlyCompletedHandovers->sum(fn($group) => $group->count())
                ]
            ]);

            return view('distribution.assigned-orders.index', compact(
                'currentPostOffice',
                'localOrders',
                'warehouseOrders', // Gộp cả đơn nội thành > 20km và ngoại thành
                'postOffices',
                'warehouses', // Chỉ lấy kho cùng tỉnh
                'recentlyCompletedHandovers'
            ));

        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách đơn hàng:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Có lỗi xảy ra khi tải danh sách đơn hàng: ' . $e->getMessage());
        }
    }

    // Hàm tính khoảng cách giữa 2 điểm
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Bán kính trái đất tính bằng km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    public function updateArrivalStatus(Request $request, DistributionHandover $handover)
    {
        try {
            $validatedData = $request->validate([
                'destination_type' => 'required|in:post_office,warehouse',
                'destination_id' => 'required|integer'
            ]);

            Log::info('Dữ liệu yêu cầu:', $request->all());

            DB::beginTransaction();

            if ($validatedData['destination_type'] === 'post_office') {
                // Kiểm tra bưu cục đích
                $postOffice = PostOffice::findOrFail($validatedData['destination_id']);

                // Chuẩn hóa dữ liệu để so sánh
                $normalizedPostOfficeDistrict = mb_strtolower(trim($postOffice->district), 'UTF-8');
                $normalizedPostOfficeProvince = mb_strtolower(trim($postOffice->province), 'UTF-8');
                $normalizedReceiverDistrict = mb_strtolower(trim($handover->order->receiver_district), 'UTF-8');
                $normalizedReceiverProvince = mb_strtolower(trim($handover->order->receiver_province), 'UTF-8');

                // Kiểm tra bưu cục đích có hợp lệ không
                if (
                    $normalizedPostOfficeProvince !== $normalizedReceiverProvince ||
                    $normalizedPostOfficeDistrict !== $normalizedReceiverDistrict
                ) {
                    throw new \Exception('Bưu cục đích không thuộc quận/huyện hoặc tỉnh/thành của người nhận');
                }

                // Log thông tin kiểm tra
                Log::info('Kiểm tra tính hợp lệ của bưu cục:', [
                    'post_office_district' => $normalizedPostOfficeDistrict,
                    'post_office_province' => $normalizedPostOfficeProvince,
                    'receiver_district' => $normalizedReceiverDistrict,
                    'receiver_province' => $normalizedReceiverProvince
                ]);

                // Cập nhật handover
                $handover->update([
                    'status' => 'completed',
                    'destination_post_office_id' => $postOffice->id,
                    'destination_warehouse_id' => null,
                    'completed_at' => now()
                ]);

                // Cập nhật order với thông tin vị trí mới
                $handover->order->update([
                    'status' => Order::STATUS_ARRIVED_WAITING_CONFIRMATION,
                    'current_location_id' => $postOffice->id,
                    'current_location_type' => PostOffice::class,
                    'current_coordinates' => $postOffice->coordinates,
                    'current_location' => $postOffice->address
                ]);

                // Tạo bản ghi post_office_orders đơn giản
                DB::table('post_office_orders')->insert([
                    'post_office_id' => $postOffice->id,
                    'order_id' => $handover->order_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Log thông tin cập nhật thành công
                Log::info('Cập nhật đơn hàng đến bưu cục thành công', [
                    'handover_id' => $handover->id,
                    'order_id' => $handover->order_id,
                    'post_office_id' => $postOffice->id,
                    'new_status' => 'completed'
                ]);
            } else {
                // Xử lý cho warehouse
                $warehouse = ProvincialWarehouse::findOrFail($validatedData['destination_id']);

                // Cập nhật handover
                $handover->update([
                    'status' => 'completed',
                    'destination_warehouse_id' => $warehouse->id,
                    'destination_post_office_id' => null,
                    'completed_at' => now()
                ]);

                // Cập nhật order với thông tin vị trí mới
                $handover->order->update([
                    'status' => 'arrived_at_warehouse',
                    'current_location_id' => $warehouse->id,
                    'current_location_type' => ProvincialWarehouse::class,
                    'current_coordinates' => $warehouse->coordinates,
                    'current_location' => $warehouse->address
                ]);

                // Tạo bản ghi warehouse_orders
                DB::table('warehouse_orders')->insert([
                    'order_id' => $handover->order_id,
                    'provincial_warehouse_id' => $warehouse->id,
                    'status' => 'entered',
                    'entered_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Log thông tin cập nhật thành công
                Log::info('Cập nhật đơn hàng đến kho thành công', [
                    'handover_id' => $handover->id,
                    'order_id' => $handover->order_id,
                    'warehouse_id' => $warehouse->id,
                    'new_status' => 'completed'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật trạng thái đến:', [
                'handover_id' => $handover->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function batchUpdateArrival(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'handover_ids' => 'required|array',
                'handover_ids.*' => 'required|exists:distribution_handovers,id',
                'destination_type' => 'required|in:post_office,warehouse',
                'destination_id' => 'required|integer'
            ]);

            DB::beginTransaction();

            // Xác định điểm đến và trạng thái mới
            $destination = null;
            $newStatus = '';
            $destinationClass = '';

            if ($validatedData['destination_type'] === 'post_office') {
                $destination = PostOffice::findOrFail($validatedData['destination_id']);
                $newStatus = Order::STATUS_ARRIVED_WAITING_CONFIRMATION;
                $destinationClass = PostOffice::class;
            } else {
                $destination = ProvincialWarehouse::findOrFail($validatedData['destination_id']);
                $newStatus = 'arrived_at_warehouse';
                $destinationClass = ProvincialWarehouse::class;
            }

            Log::info('Bắt đầu cập nhật hàng loạt:', [
                'số_lượng_đơn' => count($validatedData['handover_ids']),
                'loại_điểm_đến' => $validatedData['destination_type'],
                'id_điểm_đến' => $validatedData['destination_id']
            ]);

            $successCount = 0;
            $failedHandovers = [];

            foreach ($validatedData['handover_ids'] as $handoverId) {
                try {
                    $handover = DistributionHandover::with('order')->find($handoverId);

                    if (!$handover || $handover->status === 'completed') {
                        continue;
                    }

                    if ($validatedData['destination_type'] === 'post_office') {
                        // Kiểm tra tính hợp lệ của bưu cục đích
                        if (
                            $handover->order->receiver_province !== $destination->province ||
                            $handover->order->receiver_district !== $destination->district
                        ) {
                            $failedHandovers[] = [
                                'id' => $handoverId,
                                'reason' => 'Bưu cục đích không phù hợp với địa chỉ người nhận'
                            ];
                            continue;
                        }

                        // Cập nhật handover
                        $handover->update([
                            'status' => 'completed',
                            'destination_post_office_id' => $destination->id,
                            'destination_warehouse_id' => null,
                            'completed_at' => now()
                        ]);

                        // Cập nhật order
                        $handover->order->update([
                            'status' => $newStatus,
                            'current_location_id' => $destination->id,
                            'current_location_type' => $destinationClass,
                            'current_coordinates' => $destination->coordinates,
                            'current_location' => $destination->address
                        ]);

                        // Tạo bản ghi post_office_orders
                        DB::table('post_office_orders')->insert([
                            'post_office_id' => $destination->id,
                            'order_id' => $handover->order_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        // Cập nhật handover cho warehouse
                        $handover->update([
                            'status' => 'completed',
                            'destination_warehouse_id' => $destination->id,
                            'destination_post_office_id' => null,
                            'completed_at' => now()
                        ]);

                        // Cập nhật order
                        $handover->order->update([
                            'status' => $newStatus,
                            'current_location_id' => $destination->id,
                            'current_location_type' => $destinationClass,
                            'current_coordinates' => $destination->coordinates,
                            'current_location' => $destination->address
                        ]);

                        // Tạo bản ghi warehouse_orders
                        DB::table('warehouse_orders')->insert([
                            'order_id' => $handover->order_id,
                            'provincial_warehouse_id' => $destination->id,
                            'status' => 'entered',
                            'entered_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    $successCount++;

                    Log::info('Cập nhật thành công đơn:', [
                        'handover_id' => $handoverId,
                        'order_id' => $handover->order_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Lỗi khi cập nhật đơn:', [
                        'handover_id' => $handoverId,
                        'error' => $e->getMessage()
                    ]);

                    $failedHandovers[] = [
                        'id' => $handoverId,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $response = [
                'success' => true,
                'message' => "Đã cập nhật thành công {$successCount} đơn hàng"
            ];

            if (count($failedHandovers) > 0) {
                $response['failed_handovers'] = $failedHandovers;
                $response['message'] .= ". {count($failedHandovers)} đơn thất bại";
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật hàng loạt:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    private function findDestinationPostOffice($order)
    {
        // Tìm bưu cục có cùng quận/huyện và tỉnh với địa chỉ nhận
        $destinationPostOffice = PostOffice::where('district', $order->receiver_district)
            ->where('province', $order->receiver_province)
            ->first();

        return $destinationPostOffice;
    }

    private function createHandoverRecord($order, $distributorId, $warehouse, $isLocal = true)
    {
        if ($isLocal) {
            // Tìm bưu cục đích cho đơn nội thành
            $destinationPostOffice = $this->findDestinationPostOffice($order);
            if (!$destinationPostOffice) {
                throw new \Exception('Không tìm thấy bưu cục đích phù hợp cho đơn hàng ' . $order->tracking_number);
            }

            return DistributionHandover::create([
                'order_id' => $order->id,
                'distribution_staff_id' => $distributorId,
                'destination_warehouse_id' => $warehouse->id,
                'destination_post_office_id' => $destinationPostOffice->id,
                'shipping_type' => 'noi_thanh',
                'status' => 'pending',
                'assigned_at' => now()
            ]);
        } else {
            // Đơn ngoại thành không cần bưu cục đích
            return DistributionHandover::create([
                'order_id' => $order->id,
                'distribution_staff_id' => $distributorId,
                'destination_warehouse_id' => $warehouse->id,
                'shipping_type' => 'ngoai_thanh',
                'status' => 'pending',
                'assigned_at' => now()
            ]);
        }
    }
}
