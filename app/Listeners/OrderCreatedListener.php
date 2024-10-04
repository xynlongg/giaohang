<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\OrderDistribution;

class OrderCreatedListener
{
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        $postOffice = $order->currentLocation;

        if ($postOffice) {
            OrderDistribution::create([
                'order_id' => $order->id,
                'post_office_id' => $postOffice->id,
                'distributed_at' => now(),
            ]);
        }
    }
}