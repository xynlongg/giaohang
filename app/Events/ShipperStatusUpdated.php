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

class ShipperStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        return new Channel('shipper-updates');
    }

    public function broadcastAs()
    {
        return 'ShipperUpdated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->order->id,
            'tracking_number' => $this->order->tracking_number,
            'status' => $this->order->status,
            'shipper_name' => $this->order->distributions->first()->shipper->name ?? null,
        ];
    }
}