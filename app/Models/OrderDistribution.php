<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDistribution extends Model
{
    protected $fillable = [
        'order_id', 'shipper_id', 'post_office_id', 'distributed_by', 'distributed_at'
    ];

    protected $dates = ['distributed_at'];

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

    public function distributor()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}