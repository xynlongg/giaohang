<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostOffice extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'district', 'province', 'coordinates'];

    protected $casts = [
        'coordinates' => 'string',
    ];

    public function currentLocation()
    {
        return $this->belongsTo(PostOffice::class, 'current_location_id');
    }
    
    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }
    public function setCoordinatesAttribute($value)
    {
        $this->attributes['coordinates'] = is_string($value) ? $value : json_encode($value);
    }

    public function getCoordinatesAttribute($value)
    {
        return json_decode($value, true);
    }
}