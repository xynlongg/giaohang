<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipperProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_number',
        'work_area',
        'phone_number',
        'vehicle_type',
        'status',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}