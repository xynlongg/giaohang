<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'district',
        'province',
        'coordinates',
    ];
    protected $casts = [
        'coordinates' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}