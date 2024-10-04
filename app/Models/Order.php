<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

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
        'current_coordinates' => AsArrayObject::class,

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
    public function managingPostOffices()
    {
        return $this->belongsToMany(PostOffice::class, 'post_office_orders')
                    ->withTimestamps();
    }

    public function distributions()
    {
        return $this->hasMany(OrderDistribution::class);
    }

    public function currentDistribution()
    {
        return $this->hasOne(OrderDistribution::class)->latest('distributed_at');
    }

    public function currentPostOffice()
    {
        return $this->currentDistribution()->with('postOffice');
    }
    public function postOffices()
    {
        return $this->belongsToMany(PostOffice::class, 'post_office_orders');
    }

    public function scopeForPostOffice(Builder $query, $postOfficeId)
    {
        return $query->whereHas('distributions', function (Builder $q) use ($postOfficeId) {
            $q->where('post_office_id', $postOfficeId);
        });
    }
    public function getVietnameseStatus()
    {
        $statuses = [
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Đã hoàn thành',
            'cancelled' => 'Đã hủy',
            'assigned_to_post_office' => 'Đã gán cho bưu cục',
            // Thêm các trạng thái khác nếu cần
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColor()
    {
        $colors = [
            'pending' => 'warning',
            'confirmed' => 'info',
            'shipping' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'assigned_to_post_office' => 'secondary',
            // Thêm các trạng thái khác nếu cần
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function setCurrentCoordinatesAttribute($value)
    {
        $this->attributes['current_coordinates'] = $this->formatCoordinates($value);
    }
    private function formatCoordinates($coordinates)
    {
        if (is_string($coordinates)) {
            $coords = json_decode($coordinates, true);
        } elseif (is_array($coordinates)) {
            $coords = $coordinates;
        } else {
            throw new \InvalidArgumentException('Invalid coordinate format');
        }

        return json_encode(array_map('floatval', $coords));
    }
    public function statusUpdates()
    {
        return $this->hasMany(OrderStatusUpdate::class);
    }
    
}
