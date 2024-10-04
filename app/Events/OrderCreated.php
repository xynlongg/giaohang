<?php

namespace App\Events;

<<<<<<< HEAD
=======
use App\Models\Order;
>>>>>>> 0a21cfa (update 04/10)
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
<<<<<<< HEAD
use App\Models\Order;
=======
>>>>>>> 0a21cfa (update 04/10)

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
<<<<<<< HEAD
        return new Channel('post-office.' . $this->order->current_location_id);
    }

    public function broadcastAs()
    {
        return 'order.created';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->order->id,
            'tracking_number' => $this->order->tracking_number,
            'sender_name' => $this->order->sender_name,
            'receiver_name' => $this->order->receiver_name,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at->format('d/m/Y H:i'),
        ];
=======
        try {
            Log::info('Đang cố gắng gửi sự kiện OrderCreated trên kênh orders');
            return new Channel('orders');
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo kênh Pusher: ' . $e->getMessage());
            return null;
        }
    }
    
    public function broadcastAs()
    {
        Log::info('Gửi sự kiện OrderCreated');
        return 'OrderCreated';
    }
    
    public function broadcastWith()
    {
        try {
            Log::info('Chuẩn bị dữ liệu đơn hàng để gửi', ['order' => $this->order->toArray()]);
            return [
                'order' => $this->order->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi gửi dữ liệu đơn hàng: ' . $e->getMessage());
            return [];
        }
>>>>>>> 0a21cfa (update 04/10)
    }
}