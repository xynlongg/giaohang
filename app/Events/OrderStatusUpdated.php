<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        return new Channel('orders');
    }

    public function broadcastAs()
    {
        return 'order.updated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->order->id,
            'status' => $this->order->status,
            'tracking_number' => $this->order->tracking_number,
            'sender_name'=>$this->order->sender_name,
            'receiver_name'=>$this->order->receiver_name,
            
        ];
    }
}