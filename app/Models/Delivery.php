<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = ['shipper_id', 'address', 'status', 'latitude', 'longitude'];

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }
}
