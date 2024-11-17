<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancellationRequested extends Notification
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('Có yêu cầu hủy đơn hàng mới.')
                    ->action('Xem chi tiết', route('orders.show', $this->order))
                    ->line('Mã đơn hàng: ' . $this->order->tracking_number);
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'tracking_number' => $this->order->tracking_number,
            'message' => 'Có yêu cầu hủy đơn hàng mới.',
        ];
    }
}