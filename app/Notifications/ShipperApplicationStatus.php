<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipperApplicationStatus extends Notification
{
    use Queueable;

    private $status;

    public function __construct($status)
    {
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $statusText = $this->status === 'approved' ? 'được chấp nhận' : 'bị từ chối';
        
        return (new MailMessage)
                    ->from('longxyn@hotmail.com', 'LongXyn transport') // Thay thế bằng địa chỉ email và tên người gửi
                    ->subject('Cập nhật trạng thái đăng ký Shipper')
                    ->line('Đơn đăng ký Shipper của bạn đã ' . $statusText . '.')
                    ->line('Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!');
    }
    

    public function toArray($notifiable)
    {
        return [
            'status' => $this->status,
            'message' => $this->status === 'approved' 
                ? 'Đơn đăng ký Shipper của bạn đã được chấp nhận.' 
                : 'Đơn đăng ký Shipper của bạn đã bị từ chối.',
        ];
    }
}