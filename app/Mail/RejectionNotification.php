<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Shipper;

class RejectionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $shipper;

    public function __construct(Shipper $shipper)
    {
        $this->shipper = $shipper;
    }

    public function build()
    {
        return $this->subject('Thông báo kết quả đăng ký - LongXyn Delivery')
                    ->from('longxyn@gmail.com', 'LongXyn Delivery') // Thay đổi địa chỉ email và tên người gửi
                    ->view('emails.rejection-notification');
    }
}