<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderStatusLogController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $validatedData = $request->validate([
            'status' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $statusLog = new OrderStatusLog([
            'status' => $validatedData['status'],
            'description' => $validatedData['description'],
            'updated_by' => Auth::id(),
        ]);

        $order->statusLogs()->save($statusLog);

        return redirect()->route('orders.show', $order)->with('success', 'Trạng thái đơn hàng đã được cập nhật.');
    }

  

    public function show(OrderStatusLog $statusLog)
    {
        return view('order_status_logs.show', compact('statusLog'));
    }

    public function edit(OrderStatusLog $statusLog)
    {
        return view('order_status_logs.edit', compact('statusLog'));
    }

    public function update(Request $request, OrderStatusLog $statusLog)
    {
        $validatedData = $request->validate([
            'description' => 'nullable|string',
        ]);

        $statusLog->update($validatedData);

        return redirect()->route('orders.show', $statusLog->order)->with('success', 'Log trạng thái đã được cập nhật.');
    }

    public function destroy(OrderStatusLog $statusLog)
    {
        $order = $statusLog->order;
        $statusLog->delete();

        return redirect()->route('orders.show', $order)->with('success', 'Log trạng thái đã được xóa.');
    }
    public function index(Order $order)
        {
            $statusLogs = $order->statusLogs()->orderBy('created_at', 'desc')->get();
            return view('order_status_logs.index', compact('order', 'statusLogs'));
        }
}