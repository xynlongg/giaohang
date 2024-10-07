<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderCreatedNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail']; // Hoặc các kênh khác như database, broadcast, etc.
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Đơn hàng mới đã được tạo')
            ->line('Đơn hàng #' . $this->order->id . ' đã được tạo thành công.')
            ->action('Xem Đơn Hàng', url('/orders/' . $this->order->id))
            ->line('Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!');
    }
}
