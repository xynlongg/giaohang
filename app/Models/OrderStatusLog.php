<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'status', 'description', 'updated_by'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function currentLocation()
    {
        return $this->belongsTo(PostOffice::class, 'current_location_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }
}