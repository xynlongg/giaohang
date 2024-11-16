<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\ShipperRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompletedOrdersController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Lấy tất cả địa chỉ của user
            $userAddresses = $user->addresses()->get();
            
            // Lấy các đơn hàng đã hoàn thành
            $completedOrders = Order::whereIn('sender_name', $userAddresses->pluck('name'))
                ->whereIn('sender_phone', $userAddresses->pluck('phone'))
                ->where('status', 'delivered')
                ->with(['distributions.shipper', 'shipperRating']) // Eager load shipper và rating
                ->latest()
                ->paginate(10);

            return view('orders.completed_orders', compact('completedOrders'));
        } catch (\Exception $e) {
            Log::error('Error fetching completed orders:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi tải danh sách đơn hàng.');
        }
    }

    public function rateShipper(Request $request, Order $order)
    {
        try {
            // Validate input
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500'
            ]);

            // Kiểm tra xem đơn hàng đã được đánh giá chưa
            if ($order->shipperRating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng này đã được đánh giá'
                ], 400);
            }

            DB::beginTransaction();

            // Lấy shipper từ order
            $distribution = $order->distributions()->first();
            if (!$distribution || !$distribution->shipper) {
                throw new \Exception('Không tìm thấy thông tin shipper cho đơn hàng này.');
            }

            $shipper = $distribution->shipper;

            // Tạo đánh giá mới
            ShipperRating::create([
                'order_id' => $order->id,
                'shipper_id' => $shipper->id,
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);

            // Cập nhật vote_score nếu rating là 5 sao
            if ($request->rating == 5) {
                $shipper->increment('vote_score', 2);
                Log::info('Updated shipper vote score:', [
                    'shipper_id' => $shipper->id,
                    'old_score' => $shipper->vote_score - 2,
                    'new_score' => $shipper->vote_score
                ]);
            }

            DB::commit();

            // Tính toán thống kê đánh giá của shipper
            $stats = $this->calculateShipperStats($shipper->id);

            return response()->json([
                'success' => true,
                'message' => 'Đánh giá shipper thành công',
                'data' => [
                    'new_vote_score' => $shipper->vote_score,
                    'rating_stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rating shipper:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đánh giá shipper: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateShipperStats($shipperId)
    {
        $ratings = ShipperRating::where('shipper_id', $shipperId)->get();
        
        return [
            'total_ratings' => $ratings->count(),
            'average_rating' => $ratings->avg('rating'),
            'rating_distribution' => [
                5 => $ratings->where('rating', 5)->count(),
                4 => $ratings->where('rating', 4)->count(),
                3 => $ratings->where('rating', 3)->count(),
                2 => $ratings->where('rating', 2)->count(),
                1 => $ratings->where('rating', 1)->count(),
            ]
        ];
    }
}