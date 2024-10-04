<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\PostOffice;
use App\Models\OrderDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderDistributionController extends Controller
{
    public function distributeOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'shipper_id' => 'required|exists:users,id',
        ]);

        $order = Order::findOrFail($request->order_id);
        $shipper = User::findOrFail($request->shipper_id);
        $postOfficeUser = Auth::user();

        // Kiểm tra xem người dùng hiện tại có phải là nhân viên bưu cục không
        if (!$postOfficeUser->hasRole('post_office_staff')) {
            return response()->json(['message' => 'Bạn không có quyền phân phối đơn hàng'], 403);
        }

        // Lấy bưu cục của đơn hàng
        $orderPostOffice = $order->postOffices()->first();

        if (!$orderPostOffice) {
            return response()->json(['message' => 'Đơn hàng không thuộc về bất kỳ bưu cục nào'], 400);
        }

        // Kiểm tra xem shipper có làm việc tại bưu cục này không
        $shipperWorksAtPostOffice = $shipper->postOffices()->where('post_office_id', $orderPostOffice->id)->exists();

        if (!$shipperWorksAtPostOffice) {
            return response()->json(['message' => 'Shipper không làm việc tại bưu cục này'], 400);
        }

        DB::beginTransaction();

        try {
            // Tạo bản ghi phân phối đơn hàng
            OrderDistribution::create([
                'order_id' => $order->id,
                'shipper_id' => $shipper->id,
                'post_office_id' => $orderPostOffice->id,
                'distributed_by' => $postOfficeUser->id,
                'distributed_at' => now(),
            ]);

            // Cập nhật trạng thái đơn hàng (nếu cần)
            $order->update(['status' => 'assigned_to_shipper']);

            DB::commit();

            return response()->json(['message' => 'Đơn hàng đã được phân phối thành công cho shipper'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi xảy ra khi phân phối đơn hàng: ' . $e->getMessage()], 500);
        }
    }

    public function getAvailableShippers(Request $request)
    {
        $request->validate([
            'post_office_id' => 'required|exists:post_offices,id',
        ]);

        $postOffice = PostOffice::findOrFail($request->post_office_id);
        $availableShippers = $postOffice->shippers()->whereHas('roles', function ($query) {
            $query->where('name', 'shipper');
        })->get(['id', 'name']);

        return response()->json($availableShippers);
    }
}