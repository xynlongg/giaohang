<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostOffice extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'district', 'province', 'coordinates'];

    protected $casts = [
        'coordinates' => 'array',
    ];

    public function currentLocation()
    {
        return $this->belongsTo(PostOffice::class, 'current_location_id');
    }
    
    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }
}