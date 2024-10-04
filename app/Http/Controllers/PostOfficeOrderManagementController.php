<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\PostOffice;
use App\Models\OrderDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\OrderUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PostOfficeOrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $postOffice = $user->postOffices()->first();

        if (!$postOffice) {
            return redirect()->route('dashboard')->with('error', 'Bạn chưa được gán cho bất kỳ bưu cục nào. Vui lòng liên hệ quản trị viên.');
        }

        $query = Order::whereHas('postOffices', function($q) use ($postOffice) {
            $q->where('post_offices.id', $postOffice->id);
        })->with(['distributions' => function($q) use ($postOffice) {
            $q->where('post_office_id', $postOffice->id)->with('shipper');
        }]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('sender_name', 'like', '%' . $request->search . '%')
                  ->orWhere('receiver_name', 'like', '%' . $request->search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->latest()->paginate(10)->appends($request->all());

        $shippers = Shipper::whereHas('postOffices', function($q) use ($postOffice) {
            $q->where('post_offices.id', $postOffice->id);
        })
        ->withCount(['activeOrders' => function ($query) use ($postOffice) {
            $query->whereHas('distributions', function($q) use ($postOffice) {
                $q->where('post_office_id', $postOffice->id);
            });
        }])
        ->having('active_orders_count', '<', 20)
        ->orderByDesc('attendance_score')
        ->orderByDesc('vote_score')
        ->get();

        return view('post_offices.orders.index', compact('orders', 'shippers', 'postOffice'));
    }

    public function assignShipper(Request $request, Order $order)
    {
        Log::info('Attempting to assign shipper', ['order_id' => $order->id, 'request_data' => $request->all()]);

        try {
            $request->validate([
                'shipper_id' => 'required|exists:shippers,id'
            ]);
        
            $shipper = Shipper::findOrFail($request->shipper_id);
            $currentPostOffice = Auth::user()->postOffices()->first();
            
            if (!$currentPostOffice) {
                Log::error('Current user not associated with any post office', ['user_id' => Auth::id()]);
                return back()->with('error', 'Bạn không được gán cho bất kỳ bưu cục nào');
            }
        
            $orderBelongsToPostOffice = DB::table('post_office_orders')
                ->where('order_id', $order->id)
                ->where('post_office_id', $currentPostOffice->id)
                ->exists();
        
            if (!$orderBelongsToPostOffice) {
                Log::error('Order not associated with current post office', [
                    'order_id' => $order->id,
                    'post_office_id' => $currentPostOffice->id
                ]);
                return back()->with('error', 'Đơn hàng không thuộc về bưu cục của bạn');
            }
        
            $shipperBelongsToPostOffice = $shipper->postOffices()->where('post_offices.id', $currentPostOffice->id)->exists();
            if (!$shipperBelongsToPostOffice) {
                Log::error('Shipper not associated with the current post office', [
                    'shipper_id' => $shipper->id,
                    'post_office_id' => $currentPostOffice->id
                ]);
                return back()->with('error', 'Shipper không thuộc bưu cục này');
            }
        
            DB::beginTransaction();
            
            // Cập nhật trạng thái đơn hàng
            $order->status = 'assigned';
            $order->save();

            // Tạo hoặc cập nhật bản ghi trong bảng order_distributions
            OrderDistribution::updateOrCreate(
                ['order_id' => $order->id, 'post_office_id' => $currentPostOffice->id],
                [
                    'shipper_id' => $shipper->id,
                    'distributed_by' => Auth::id(),
                    'distributed_at' => now()
                ]
            );

            DB::commit();
        
            event(new OrderUpdated($order));
        
            Log::info('Shipper assigned successfully', [
                'order_id' => $order->id,
                'shipper_id' => $shipper->id,
                'post_office_id' => $currentPostOffice->id
            ]);
        
            return back()->with('success', 'Đã gán shipper thành công cho đơn hàng #' . $order->tracking_number);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign shipper', [
                'order_id' => $order->id,
                'shipper_id' => $request->shipper_id,
                'post_office_id' => $currentPostOffice->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Có lỗi xảy ra khi gán shipper: ' . $e->getMessage());
        }
    }
}