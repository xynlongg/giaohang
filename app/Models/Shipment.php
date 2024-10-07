<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = ['user_id', 'name', 'phone', 'address', 'coordinates'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}