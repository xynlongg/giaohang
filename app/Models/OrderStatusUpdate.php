<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusUpdate extends Model
{
    protected $fillable = [
        'order_id', 'shipper_id', 'post_office_id', 'status', 'reason', 'custom_reason', 'image'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shipper()
    {
        return $this->belongsTo(Shipper::class);    
    }

    public function postOffice()
    {
        return $this->belongsTo(PostOffice::class);
    }
}