<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  OrderCreated  $event
     * @return void
     */
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        Log::info('Handling OrderCreated event', ['order_id' => $order->id]);

        // Gửi thông báo cho người dùng
        $user = $order->user;
        if ($user) {
            $user->notify(new OrderCreatedNotification($order));
            Log::info('Order notification sent to user', ['order_id' => $order->id, 'user_id' => $user->id]);
        } else {
            Log::warning('No user associated with order', ['order_id' => $order->id]);
        }
    }
}