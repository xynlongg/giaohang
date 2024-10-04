<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostOffice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'district', 'province', 'coordinates', 'latitude', 'longitude'
    ];
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
        $this->attributes['latitude'] = $value[1];
        $this->attributes['longitude'] = $value[0];
    }

    public function getCoordinatesAttribute($value)
    {
        return json_decode($value, true);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'post_office_user');
    }
    public function managedOrders()
    {
        return $this->belongsToMany(Order::class, 'post_office_orders')
                    ->withTimestamps();
    }
    public function shippers()
    {
        return $this->belongsToMany(Shipper::class, 'post_office_shippers')->withTimestamps();
    }
}