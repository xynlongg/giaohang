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

class OrderReassigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $oldShipperId;

    public function __construct(Order $order, $oldShipperId)
    {
        $this->order = $order;
        $this->oldShipperId = $oldShipperId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('shipper.' . $this->oldShipperId);
    }

    public function broadcastAs()
    {
        return 'OrderReassigned';
    }
}