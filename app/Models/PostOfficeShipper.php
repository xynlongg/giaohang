<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostOfficeShipper extends Model
{
    use HasFactory;

    protected $fillable = [
       'post_office_id', 'shipper_id'
        ];

    public function postOffice()
    {
        return $this->belongsTo(PostOffice::class);
    }
}