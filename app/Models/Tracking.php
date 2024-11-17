<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    use HasFactory;
    protected $fillable = ['order_id', 'status', 'start_coordinates', 'end_coordinates'];

    protected $casts = [
        'start_coordinates' => 'array',
        'end_coordinates' => 'array',
    ];
}
