<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ShipperResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Shipper extends Authenticatable implements JWTSubject
{
    use HasFactory, HasApiTokens,Notifiable;


    protected $fillable = [
        'name', 'phone', 'email', 'avatar', 'cccd', 'job_type', 'city', 'district', 'status',
        'password', 'attendance_score', 'vote_score', 'operating_area','api_token',
    ];

    protected $hidden = [
        'password', 'remember_token','api_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ShipperResetPasswordNotification($token));
    }

    public function getEmailForPasswordReset()
    {
        return $this->email;
    }
   
    public function orders()
    {
        return $this->hasMany(Order::class, 'shipper_id');
    }
    public function activeOrders()
    {
        return $this->hasManyThrough(Order::class, OrderDistribution::class, 'shipper_id', 'id', 'id', 'order_id')
            ->whereIn('orders.status', ['assigned', 'in_transit']);
    }
    public function distributions()
    {
        return $this->hasMany(OrderDistribution::class);
    }
    public function postOffices()
    {
        return $this->belongsToMany(PostOffice::class, 'post_office_shippers')->withTimestamps();
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function postOfficeShipper()
    {
        return $this->hasOne(PostOfficeShipper::class);
    }
}
