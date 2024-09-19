<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ShipperResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Notifiable;

class Shipper extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name', 'phone', 'email', 'avatar', 'cccd', 'job_type', 'city', 'district', 'status',
        'password', 'attendance_score', 'vote_score', 'operating_area'
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ShipperResetPasswordNotification($token));
    }

    public function getEmailForPasswordReset()
    {
        return $this->email;
    }
}
