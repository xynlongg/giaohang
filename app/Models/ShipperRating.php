<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShipperRating extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'shipper_id',
        'user_id',
        'rating',
        'comment'
    ];

    // Relationship với Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relationship với Shipper
    public function shipper()
    {
        return $this->belongsTo(Shipper::class);
    }

    // Relationship với User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}