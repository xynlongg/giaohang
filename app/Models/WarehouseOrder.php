<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseOrder extends Model
{
    protected $fillable = [
        'order_id',
        'provincial_warehouse_id',
        'entered_at',
        'status',
        'processed_at',
        'completed_at'
    ];

    protected $dates = [
        'entered_at',
        'processed_at', 
        'completed_at',
        'created_at',
        'updated_at'
    ];
    
    // hoặc có thể dùng $casts
    protected $casts = [
        'entered_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(ProvincialWarehouse::class, 'provincial_warehouse_id');
    }
}