<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'sender_name', 'sender_phone', 'sender_address', 'sender_coordinates',
        'receiver_name', 'receiver_phone', 'receiver_address', 'receiver_coordinates',
        'is_pickup_at_post_office', 'pickup_location_id', 'pickup_date', 'delivery_date',
        'total_weight', 'total_cod', 'total_value', 'shipping_fee', 'total_amount', 'status', 
        'tracking_number', 'qr_code', 
        'current_location_id',
        'current_location_type',
        'current_coordinates',
        'current_location',
        'warranty_fee',
        'category_id',
        'warranty_package_id',
    ];

    protected $casts = [
        'sender_coordinates' => 'array',
        'receiver_coordinates' => 'array',
        'current_coordinates' => 'array',
        'is_pickup_at_post_office' => 'boolean',
        'pickup_date' => 'datetime',
        'delivery_date' => 'datetime',
        'current_coordinates' => 'array',

    ];

    public function pickupLocation()
    {
        return $this->belongsTo(PostOffice::class, 'pickup_location_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product')
            ->withPivot('quantity', 'cod_amount', 'weight')
            ->withTimestamps();
    }
    
    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function currentLocation()
    {
        return $this->belongsTo(PostOffice::class, 'current_location_id');
    }

    public function getQrCodeAttribute($value)
    {
        return $value ? $value : null;
    }

    public function setQrCodeAttribute($value)
    {
        $this->attributes['qr_code'] = $value ? $value : null;
    }
    public function locationHistory()
    {
        return $this->hasMany(OrderLocationHistory::class);
    }
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function warrantyPackage()
    {
        return $this->belongsTo(WarrantyPackage::class);
    }
}
