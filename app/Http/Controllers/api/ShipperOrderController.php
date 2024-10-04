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
                Log::error('Unauthorized access attempt');
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            Log::info('Received data for order status update:', $request->all());
            Log::info('Authenticated shipper:', ['shipper_id' => $shipper->id]);

            $validatedData = $request->validate([
                'status' => 'required|in:picked_up,in_transit,delivered,failed_pickup,failed_delivery',
                'reason' => 'required_if:status,failed_pickup,failed_delivery',
                'custom_reason' => 'required_if:reason,other',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            DB::beginTransaction();

            try {
                $order->status = $validatedData['status'];
                $order->save();

                $statusUpdate = new OrderStatusUpdate([
                    'order_id' => $order->id,
                    'shipper_id' => $shipper->id,
                    'post_office_id' => $shipper->postOfficeShipper->post_office_id,
                    'status' => $validatedData['status'],
                    'reason' => $validatedData['reason'] ?? null,
                    'custom_reason' => $validatedData['custom_reason'] ?? null,
                ]);

                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('order_status_images', 'public');
                    $statusUpdate->image = $imagePath;
                }

                $statusUpdate->save();

                DB::commit();

                Log::info('Order status updated successfully', ['order_id' => $order->id, 'new_status' => $validatedData['status']]);

                // Fetch the updated order with status updates
                $updatedOrder = Order::with(['statusUpdates' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }])->find($order->id);

                // Transform the image URLs
                $updatedOrder->statusUpdates->transform(function ($update) {
                    if ($update->image) {
                        $update->image = Storage::url($update->image);
                    }
                    return $update;
                });

                return response()->json([
                    'message' => 'Order status updated successfully',
                    'order' => $updatedOrder
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error in database transaction while updating order status', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (ValidationException $e) {
            Log::error('Validation error in updateOrderStatus', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in updateOrderStatus', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred while updating the order status.',
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
}