<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tracking_number',
        'status',
        'start_point',
        'end_point',
        'current_location'
    ];

    // protected $casts = [
    //     'start_point' => 'geometry',
    //     'end_point' => 'geometry',
    //     'current_location' => 'geometry',
    // ];
}