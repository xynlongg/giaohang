<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusUpdate; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Events\ShipperStatusUpdated;


class ShipperOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:shipper');
    }
    public function getAssignedOrders(Request $request)
    {
        $shipper = Auth::guard('shipper')->user();

        if (!$shipper) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = Order::whereHas('distributions', function ($query) use ($shipper) {
            $query->where('shipper_id', $shipper->id);
        })
        ->with(['distributions' => function ($query) use ($shipper) {
            $query->where('shipper_id', $shipper->id)->with('postOffice');
        }])
        ->when($request->status, function ($query, $status) {
            return $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        try {
            $shipper = Auth::guard('shipper')->user();

            if (!$shipper) {
                Log::error('Nỗ lực truy cập trái phép');
                return response()->json(['message' => 'Không được phép'], 401);
            }

            $validatedData = $request->validate([
                'status' => 'required|in:' . implode(',', Order::getStatuses()),
            ]);

            if (!$order->canUpdateStatusTo($validatedData['status'])) {
                Log::warning('Chuyển đổi trạng thái không hợp lệ', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                    'requested_status' => $validatedData['status']
                ]);
                return response()->json([
                    'message' => 'Không thể cập nhật trạng thái đơn hàng',
                    'current_status' => $order->status,
                    'requested_status' => $validatedData['status']
                ], 422);
            }

            DB::beginTransaction();

            $order->status = $validatedData['status'];

            // Nếu trạng thái mới là 'arrived_at_post_office', cập nhật tọa độ và vị trí
            if ($validatedData['status'] === 'arrived_at_post_office') {
                $postOffice = $order->postOffices()->first();
                if ($postOffice) {
                    $order->current_location_id = $postOffice->id;
                    $order->current_location_type = 'post_office';
                    $order->current_coordinates = $postOffice->coordinates;
                    $order->current_location = $postOffice->address;
                } else {
                    Log::warning('Không tìm thấy bưu cục cho đơn hàng', ['order_id' => $order->id]);
                }
            }

            $order->save();

            DB::commit();

            Log::info('Trạng thái đơn hàng đã cập nhật thành công', ['order_id' => $order->id, 'new_status' => $validatedData['status']]);

            return response()->json([
                'message' => 'Trạng thái đơn hàng đã cập nhật thành công',
                'order' => $order
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Lỗi xác thực', ['errors' => $e->errors()]);
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi trong updateOrderStatus', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi cập nhật trạng thái đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getOrderDetail($id)
    {
        $shipper = Auth::guard('shipper')->user();

        if (!$shipper) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::whereHas('distributions', function ($query) use ($shipper) {
            $query->where('shipper_id', $shipper->id);
        })->with(['distributions.postOffice', 'statusUpdates' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found or not assigned to you'], 404);
        }

        // Transform the order data to include full image URLs
        $order->statusUpdates->transform(function ($update) {
            if ($update->image) {
                $update->image = asset('storage/' . $update->image);
            }
            return $update;
        });

        return response()->json($order);
    }

    public function getShipperPostOffice($shipperId)
    {
        $shipper = Auth::guard('shipper')->user();
        if (!$shipper || $shipper->id != $shipperId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $postOfficeShipper = PostOfficeShipper::where('shipper_id', $shipperId)
            ->with('postOffice')
            ->first();

        if (!$postOfficeShipper || !$postOfficeShipper->postOffice) {
            return response()->json(['message' => 'Post office not found for this shipper'], 404);
        }

        return response()->json([
            'id' => $postOfficeShipper->postOffice->id,
            'name' => $postOfficeShipper->postOffice->name,
            // Thêm các trường khác của bưu cục nếu cần
        ]);
    }
    public function getPostOffice($orderId)
    {
        $shipper = Auth::guard('shipper')->user();
        if (!$shipper) {
            \Log::error('Unauthorized access attempt to getPostOffice', ['order_id' => $orderId]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $order = Order::findOrFail($orderId);
            $postOffice = $order->postOffices()->first();
            
            if (!$postOffice) {
                \Log::warning('Post office not found for order', ['order_id' => $orderId]);
                return response()->json(['error' => 'Không tìm thấy thông tin bưu cục'], 404);
            }
            
            \Log::info('Post office data being returned:', $postOffice->toArray());
            
            return response()->json($postOffice);
        } catch (\Exception $e) {
            \Log::error('Error in getPostOffice', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Đã xảy ra lỗi khi lấy thông tin bưu cục'], 500);
        }
    }

    public function checkImageExists($imageName)
    {
        $exists = Storage::disk('public')->exists("order_status_images/{$imageName}");
        
        if ($exists) {
            $fullPath = Storage::disk('public')->path("order_status_images/{$imageName}");
            $url = Storage::disk('public')->url("order_status_images/{$imageName}");
            
            return response()->json([
                'exists' => true,
                'fullPath' => $fullPath,
                'url' => $url
            ]);
        } else {
            return response()->json([
                'exists' => false,
                'message' => 'Image not found'
            ]);
        }
    }

    public function getDeliveryOrderDetail($id)
    {
        try {
            $shipper = Auth::guard('shipper')->user();
            if (!$shipper) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
    
            Log::info('Looking for delivery order:', ['id' => $id, 'shipper_id' => $shipper->id]);
    
            // Lấy đơn hàng với thông tin liên quan
            $order = Order::with(['distributions.postOffice', 'statusUpdates'])
                ->whereHas('distributions', function ($query) use ($shipper) {
                    $query->where('shipper_id', $shipper->id);
                })
                ->where('id', $id)
                ->where('status', 'out_for_delivery')
                ->first();
    
            if (!$order) {
                Log::warning('Order not found or not assigned to shipper', [
                    'order_id' => $id,
                    'shipper_id' => $shipper->id
                ]);
                return response()->json([
                    'message' => 'Không tìm thấy đơn hàng hoặc đơn hàng không được gán cho bạn'
                ], 404);
            }
    
            // Transform status updates để bao gồm full URLs cho images
            if ($order->statusUpdates) {
                $order->statusUpdates->transform(function ($update) {
                    if ($update->image) {
                        $update->image = asset('storage/' . $update->image);
                    }
                    return $update;
                });
            }
    
            $postOffice = $order->distributions->first()->postOffice;
    
            Log::info('Delivery order details retrieved successfully', [
                'order_id' => $order->id,
                'shipper_id' => $shipper->id
            ]);
    
            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'receiver_name' => $order->receiver_name,
                    'receiver_phone' => $order->receiver_phone,
                    'receiver_address' => $order->receiver_address,
                    'receiver_coordinates' => $order->receiver_coordinates,
                    'statusUpdates' => $order->statusUpdates
                ],
                'post_office' => [
                    'name' => $postOffice->name,
                    'address' => $postOffice->address,
                    'coordinates' => $postOffice->coordinates
                ]
            ]);
    
        } catch (\Exception $e) {
            Log::error('Error in getDeliveryOrderDetail:', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin đơn hàng'
            ], 500);
        }
    }
    
    public function getDeliveryOrders(Request $request)
    {
        try {
            $shipper = Auth::guard('shipper')->user();

            if (!$shipper) {
                return response()->json([
                    'message' => 'Không có quyền truy cập'
                ], 401);
            }

            Log::info('Fetching delivery orders for shipper:', [
                'shipper_id' => $shipper->id,
                'filters' => [
                    'date' => $request->date,
                    'page' => $request->page
                ]
            ]);

            // Sử dụng Eloquent thay vì Query Builder
            $query = Order::with(['distributions.postOffice'])
                ->whereHas('distributions', function($query) use ($shipper) {
                    $query->where('shipper_id', $shipper->id);
                })
                ->where('status', 'out_for_delivery')
                ->select([
                    'orders.id',
                    'orders.tracking_number',
                    'orders.receiver_name',
                    'orders.receiver_phone',
                    'orders.receiver_address',
                    'orders.status',
                    'orders.created_at'
                ]);

            if ($request->date) {
                $query->whereDate('orders.created_at', $request->date);
            }

            $deliveryOrders = $query->orderBy('orders.created_at', 'desc')
                ->paginate(10);

            // Transform data để lấy thêm thông tin bưu cục
            $transformedData = $deliveryOrders->through(function ($order) {
                $postOffice = $order->distributions->first()->postOffice;
                return [
                    'id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'receiver_name' => $order->receiver_name,
                    'receiver_phone' => $order->receiver_phone,
                    'receiver_address' => $order->receiver_address,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'post_office_name' => $postOffice ? $postOffice->name : null,
                    'distributed_at' => $order->distributions->first()->created_at
                ];
            });

            Log::info('Delivery orders fetched successfully', [
                'total_orders' => $deliveryOrders->total(),
                'current_page' => $deliveryOrders->currentPage(),
                'last_page' => $deliveryOrders->lastPage()
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedData->items(),
                'meta' => [
                    'current_page' => $deliveryOrders->currentPage(),
                    'last_page' => $deliveryOrders->lastPage(),
                    'per_page' => $deliveryOrders->perPage(),
                    'total' => $deliveryOrders->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi trong getDeliveryOrders:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải danh sách đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDeliveryOrderStatus(Request $request, $id)
    {
        try {
            $shipper = Auth::guard('shipper')->user();
            if (!$shipper) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Tìm đơn hàng và bưu cục
            $order = Order::whereHas('distributions', function($query) use ($shipper) {
                    $query->where('shipper_id', $shipper->id);
                })
                ->with(['distributions.postOffice'])
                ->where('id', $id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng hoặc không có quyền truy cập'
                ], 404);
            }

            // Lấy post_office_id từ distribution
            $distribution = $order->distributions->first();
            if (!$distribution || !$distribution->postOffice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin bưu cục cho đơn hàng này'
                ], 404);
            }

            // Validate
            $validatedData = $request->validate([
                'status' => 'required|in:delivered,failed_delivery',
                'image' => 'required_if:status,delivered|image|max:2048',
                'reason' => 'nullable|string',
                'custom_reason' => 'nullable|string'
            ]);

            DB::beginTransaction();

            // Cập nhật trạng thái đơn hàng
            $updateData = [
                'status' => $validatedData['status']
            ];

            // Upload và lưu ảnh nếu có
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('delivery_confirmations', 'public');
                $updateData['delivery_image'] = $imagePath;
            }

            // Cập nhật đơn hàng
            $order->update($updateData);

            // Tạo bản ghi trạng thái mới với đầy đủ thông tin
            DB::table('order_status_updates')->insert([
                'order_id' => $order->id,
                'shipper_id' => $shipper->id,
                'post_office_id' => $distribution->postOffice->id,
                'status' => $validatedData['status'],
                'reason' => $validatedData['reason'] ?? null,
                'custom_reason' => $validatedData['custom_reason'] ?? null,
                'image' => $imagePath,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Cập nhật bảng deliveries
            DB::table('order_deliveries')
                ->where('order_id', $order->id)
                ->where('shipper_id', $shipper->id)
                ->update([
                    'status' => $validatedData['status'],
                    'completed_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'order' => $order
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in updateDeliveryOrderStatus:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái'
            ], 500);
        }
    }
}