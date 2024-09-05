<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLocationHistory extends Model
{
    protected $fillable = [
        'order_id',
        'location_type',
        'location_id',
        'address',
        'coordinates',
        'status',
        'timestamp',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'timestamp' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function postOffice()
    {
        return $this->belongsTo(PostOffice::class, 'location_id');
    }
}