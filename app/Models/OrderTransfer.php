<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'from_post_office_id',
        'to_post_office_id',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function fromPostOffice()
    {
        return $this->belongsTo(PostOffice::class, 'from_post_office_id');
    }

    public function toPostOffice()
    {
        return $this->belongsTo(PostOffice::class, 'to_post_office_id');
    }
}