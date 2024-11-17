<?php


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Shipper;

class ShipperRegistrationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $shipper;
    public $defaultPassword;

    public function __construct(Shipper $shipper, $defaultPassword)
    {
        $this->shipper = $shipper;
        $this->defaultPassword = $defaultPassword;
    }

    public function build()
    {
        return $this->subject('Mời phỏng vấn - LongXyn Delivery')
                    ->from('longxyn@gmail.com', 'LongXyn Delivery')
                    ->view('emails.interview-invitation')
                    ->with([
                        'shipperName' => $this->shipper->name,
                        'defaultPassword' => $this->defaultPassword
                    ]);
    }
}