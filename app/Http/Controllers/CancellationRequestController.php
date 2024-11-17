<?php

namespace App\Http\Controllers;

use App\Models\OrderCancellationRequest;
use App\Models\PostOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CancellationRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userPostOffices = $user->postOffices->pluck('id')->toArray();

        $cancellationRequests = OrderCancellationRequest::whereHas('order.postOffices', function ($query) use ($userPostOffices) {
            $query->whereIn('post_offices.id', $userPostOffices);
        })->with(['order', 'user', 'postOffice'])->paginate(10);

        return view('cancellation_requests.index', compact('cancellationRequests'));
    }

    public function show(OrderCancellationRequest $cancellationRequest)
    {
        $this->authorize('view', $cancellationRequest);

        return view('cancellation_requests.show', compact('cancellationRequest'));
    }

    public function process(Request $request, OrderCancellationRequest $cancellationRequest)
    {
        $this->authorize('process', $cancellationRequest);

        $validatedData = $request->validate([
            'status' => 'required|in:approved,rejected',
            'comment' => 'nullable|string|max:255',
        ]);

        $cancellationRequest->update([
            'status' => $validatedData['status'],
            'admin_comment' => $validatedData['comment'],
        ]);

        if ($validatedData['status'] === 'approved') {
            $cancellationRequest->order->update(['status' => 'cancelled']);
            // Thêm logic xử lý khi đơn hàng được hủy (ví dụ: hoàn tiền, cập nhật kho, v.v.)
        }

        return redirect()->route('cancellation-requests.index')->with('success', 'Yêu cầu hủy đơn đã được xử lý.');
    }
}