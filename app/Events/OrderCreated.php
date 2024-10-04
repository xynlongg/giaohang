<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

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
    }
}