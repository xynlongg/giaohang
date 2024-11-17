<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory;
    protected $appends = ['calculated_distance'];
    public function getCalculatedDistanceAttribute()
    {
        // Implement the logic to calculate distance here
        // For example:
        return $this->calculateDistance(
            $this->current_coordinates[1],
            $this->current_coordinates[0],
            $this->receiver_coordinates[1],
            $this->receiver_coordinates[0]
        );
    }
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // in kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        return $distance;
    }
    protected $fillable = [
        'tracking_number', 'qr_code', 'sender_name', 'sender_phone', 'sender_address',
        'sender_district', 'sender_province', 'sender_coordinates',
        'receiver_name', 'receiver_phone', 'receiver_address',
        'receiver_district', 'receiver_province', 'receiver_coordinates',
        'is_pickup_at_post_office', 'pickup_location_id', 'pickup_date', 'delivery_date',
        'total_weight', 'total_cod', 'total_value', 'shipping_fee', 'warranty_fee',
        'total_amount', 'shipping_type', 'current_location_id', 'current_location_type',
        'current_coordinates', 'current_location', 'category_id', 'warranty_package_id',
        'cancellation_requested_at', 'user_id', 'status',
    ];

    protected $casts = [
        'sender_coordinates' => 'array',
        'receiver_coordinates' => 'array',
        'current_coordinates' => AsArrayObject::class,
        'is_pickup_at_post_office' => 'boolean',
        'pickup_date' => 'datetime',
        'delivery_date' => 'datetime',
    ];

    // Constants for all statuses
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    const STATUS_PICKUP_ASSIGNED = 'pickup_assigned';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_ARRIVED_AT_ORIGIN_POST_OFFICE = 'arrived_at_origin_post_office';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED_AT_DESTINATION_POST_OFFICE = 'arrived_at_destination_post_office';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED_PICKUP = 'failed_pickup';
    const STATUS_FAILED_DELIVERY = 'failed_delivery';
    const STATUS_RETURNED_TO_SENDER = 'returned_to_sender';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_READY_FOR_LOCAL_DELIVERY = 'ready_for_local_delivery';
    const STATUS_READY_FOR_DIRECT_DELIVERY = 'ready_for_direct_delivery';
    const STATUS_READY_FOR_MAIN_WAREHOUSE = 'ready_for_main_warehouse';
    const STATUS_ARRIVED_AT_POST_OFFICE = 'arrived_at_post_office';
    const STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE = 'transferring_to_provincial_warehouse';
    const STATUS_ARRIVED_AT_WAREHOUSE = 'arrived_at_warehouse';
    const STATUS_ASSIGNED_TO_STAFF = 'assigned_to_staff';  
    const STATUS_ASSIGNED_TO_SHIPPER = 'assigned_to_shipper';
    const STATUS_PROCESSING = 'processing';
    const STATUS_TRANSFERRING = 'transferring';
    const STATUS_TRANSFERRING_TO_DELIVERY_POST_OFFICE = 'transferring_to_post_office';
    const STATUS_ARRIVED_WAITING_CONFIRMATION = 'arrived_waiting_confirmation';
    const STATUS_CONFIRMED_AT_DESTINATION = 'confirmed_at_destination'; 
    const STATUS_IN_DELIVERY = 'in_delivery';

    
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_PICKUP_ASSIGNED,
            self::STATUS_PICKED_UP,
            self::STATUS_ARRIVED_AT_ORIGIN_POST_OFFICE,
            self::STATUS_IN_TRANSIT,
            self::STATUS_ARRIVED_AT_DESTINATION_POST_OFFICE,
            self::STATUS_ARRIVED_AT_POST_OFFICE,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED_PICKUP,
            self::STATUS_FAILED_DELIVERY,
            self::STATUS_RETURNED_TO_SENDER,
            self::STATUS_CANCELLED,
            self::STATUS_READY_FOR_LOCAL_DELIVERY,
            self::STATUS_READY_FOR_DIRECT_DELIVERY,
            self::STATUS_READY_FOR_MAIN_WAREHOUSE,
            self::STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE,
            self::STATUS_ARRIVED_AT_WAREHOUSE,
            self::STATUS_TRANSFERRING_TO_DELIVERY_POST_OFFICE, 
            self::STATUS_ARRIVED_WAITING_CONFIRMATION,
            self::STATUS_CONFIRMED_AT_DESTINATION, 
            self::STATUS_IN_DELIVERY,



        ];
    }

    public function canUpdateStatusTo($newStatus)
    {
        $currentStatus = $this->status;
        $allowedTransitions = [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_PICKUP_ASSIGNED
            ,self::STATUS_ASSIGNED_TO_STAFF, self::STATUS_ASSIGNED_TO_SHIPPER],
            self::STATUS_CONFIRMED => [self::STATUS_READY_FOR_PICKUP, self::STATUS_CANCELLED, self::STATUS_PICKUP_ASSIGNED],
            self::STATUS_READY_FOR_PICKUP => [self::STATUS_PICKUP_ASSIGNED, self::STATUS_CANCELLED],
            self::STATUS_PICKUP_ASSIGNED => [self::STATUS_PICKED_UP, self::STATUS_FAILED_PICKUP],
            self::STATUS_PICKED_UP => [self::STATUS_ARRIVED_AT_ORIGIN_POST_OFFICE, self::STATUS_ARRIVED_AT_POST_OFFICE],
            self::STATUS_ARRIVED_AT_POST_OFFICE => [
                self::STATUS_IN_TRANSIT, 
                self::STATUS_READY_FOR_LOCAL_DELIVERY,
                self::STATUS_READY_FOR_DIRECT_DELIVERY,
                self::STATUS_READY_FOR_MAIN_WAREHOUSE,
                self::STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE,
                self::STATUS_TRANSFERRING_TO_DELIVERY_POST_OFFICE, 
                self::STATUS_ARRIVED_WAITING_CONFIRMATION, 

            ],
            self::STATUS_ARRIVED_AT_ORIGIN_POST_OFFICE => [self::STATUS_IN_TRANSIT],
            self::STATUS_IN_TRANSIT => [self::STATUS_ARRIVED_AT_DESTINATION_POST_OFFICE, self::STATUS_ARRIVED_AT_POST_OFFICE],
            self::STATUS_ARRIVED_AT_DESTINATION_POST_OFFICE => [self::STATUS_OUT_FOR_DELIVERY],
            self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED, self::STATUS_FAILED_DELIVERY],
            self::STATUS_FAILED_PICKUP => [self::STATUS_PICKUP_ASSIGNED, self::STATUS_CANCELLED],
            self::STATUS_FAILED_DELIVERY => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_RETURNED_TO_SENDER],
            self::STATUS_RETURNED_TO_SENDER => [self::STATUS_DELIVERED],
            self::STATUS_READY_FOR_LOCAL_DELIVERY => [self::STATUS_OUT_FOR_DELIVERY],
            self::STATUS_READY_FOR_DIRECT_DELIVERY => [self::STATUS_IN_TRANSIT],
            self::STATUS_READY_FOR_MAIN_WAREHOUSE => [self::STATUS_IN_TRANSIT],
            self::STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE => [
                self::STATUS_ARRIVED_AT_WAREHOUSE
            ],
            self::STATUS_ARRIVED_AT_WAREHOUSE => [
                self::STATUS_IN_TRANSIT,
                self::STATUS_OUT_FOR_DELIVERY
            ],
            self::STATUS_ASSIGNED_TO_STAFF => [
                self::STATUS_PROCESSING,
                self::STATUS_TRANSFERRING
            ],
            self::STATUS_ARRIVED_WAITING_CONFIRMATION => [
                self::STATUS_CONFIRMED_AT_DESTINATION
            ],
            self::STATUS_CONFIRMED_AT_DESTINATION => [
                self::STATUS_OUT_FOR_DELIVERY
            ],
            self::STATUS_OUT_FOR_DELIVERY => [
                self::STATUS_IN_DELIVERY,
                self::STATUS_DELIVERED,
                self::STATUS_FAILED_DELIVERY
            ],
            self::STATUS_IN_DELIVERY => [
                self::STATUS_DELIVERED,
                self::STATUS_FAILED_DELIVERY
            ],

        ];
    
        Log::info('Kiểm tra chuyển đổi trạng thái', [
            'order_id' => $this->id,
            'current_status' => $currentStatus,
            'new_status' => $newStatus,
            'allowed_transitions' => $allowedTransitions[$currentStatus] ?? []
        ]);
    
        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }
    public function canStartDelivery()
    {
        return $this->status === self::STATUS_OUT_FOR_DELIVERY;
    }
    
    public static function isValidStatus($status)
    {
        return in_array($status, self::getStatuses());
    }

    public function setStatusAttribute($value)
    {
        if (self::isValidStatus($value)) {
            $this->attributes['status'] = $value;
        } else {
            throw new \InvalidArgumentException("Invalid status: $value");
        }
    }
    // Relationships
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

    public function provincialWarehouses()
    {
        return $this->belongsToMany(ProvincialWarehouse::class, 'warehouse_orders')
                    ->withPivot('entered_at', 'left_at', 'status')
                    ->withTimestamps();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForPostOffice(Builder $query, $postOfficeId)
    {
        return $query->whereHas('distributions', function (Builder $q) use ($postOfficeId) {
            $q->where('post_office_id', $postOfficeId);
        });
    }

    // Attribute Getters and Setters
    public function getQrCodeAttribute($value)
    {
        return $value ? $value : null;
    }

    public function setQrCodeAttribute($value)
    {
        $this->attributes['qr_code'] = $value ? $value : null;
    }

    public function setCurrentCoordinatesAttribute($value)
    {
        $this->attributes['current_coordinates'] = $this->formatCoordinates($value);
    }

    // Helper Methods
    public function getVietnameseStatus()
    {
        $statuses = [
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_READY_FOR_PICKUP => 'Sẵn sàng để lấy hàng',
            self::STATUS_PICKUP_ASSIGNED => 'Đã phân công lấy hàng',
            self::STATUS_PICKED_UP => 'Đã lấy hàng',
            self::STATUS_ARRIVED_AT_ORIGIN_POST_OFFICE => 'Đã đến bưu cục gốc',
            self::STATUS_IN_TRANSIT => 'Đang vận chuyển',
            self::STATUS_ARRIVED_AT_DESTINATION_POST_OFFICE => 'Đã đến bưu cục đích',
            self::STATUS_ARRIVED_AT_POST_OFFICE => 'Đã đến bưu cục',
            self::STATUS_OUT_FOR_DELIVERY => 'Đang giao hàng',
            self::STATUS_DELIVERED => 'Đã giao hàng',
            self::STATUS_FAILED_PICKUP => 'Lấy hàng thất bại',
            self::STATUS_FAILED_DELIVERY => 'Giao hàng thất bại',
            self::STATUS_RETURNED_TO_SENDER => 'Đã trả lại người gửi',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_READY_FOR_LOCAL_DELIVERY => 'Sẵn sàng giao hàng nội bộ',
            self::STATUS_READY_FOR_DIRECT_DELIVERY => 'Sẵn sàng giao hàng trực tiếp',
            self::STATUS_READY_FOR_MAIN_WAREHOUSE => 'Sẵn sàng chuyển đến kho chính',
            self::STATUS_ARRIVED_AT_WAREHOUSE => 'Đã đến kho tổng',
            self::STATUS_TRANSFERRING_TO_PROVINCIAL_WAREHOUSE => 'Đang chuyển đến kho tổng',
            self::STATUS_TRANSFERRING_TO_DELIVERY_POST_OFFICE => 'Đang chuyển đến bưu cục giao hàng',
            self::STATUS_ARRIVED_WAITING_CONFIRMATION => 'Đã đến bưu cục giao hàng - Chờ xác nhận',
            self::STATUS_CONFIRMED_AT_DESTINATION => 'Đã xác nhận đến',
            self::STATUS_IN_DELIVERY => 'Đang giao hàng'
            
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColor()
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_IN_TRANSIT => 'primary',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_ARRIVED_WAITING_CONFIRMATION => 'warning',
            self::STATUS_CONFIRMED_AT_DESTINATION => 'info',
            // Add colors for other statuses as needed
        ];

        return $colors[$this->status] ?? 'secondary';
    }
    public function statusUpdates()
    {
        return $this->hasMany(OrderStatusUpdate::class);
    }
    public function updateTotals()
    {
        $totalWeight = 0;
        $totalCod = 0;
        $totalValue = 0;

        foreach ($this->products as $product) {
            $totalWeight += $product->pivot->weight * $product->pivot->quantity;
            $totalCod += $product->pivot->cod_amount * $product->pivot->quantity;
            $totalValue += $product->value * $product->pivot->quantity;
        }

        $this->total_weight = $totalWeight;
        $this->total_cod = $totalCod;
        $this->total_value = $totalValue;
        $this->shipping_fee = $this->calculateShippingFee($totalWeight);
        $this->total_amount = $totalCod + $this->shipping_fee;

        $this->save();
    }

    private function calculateShippingFee($weight)
    {
        // Implement your shipping fee calculation logic here
        $baseRate = 20000; // VND
        $ratePerKg = 5000; // VND per kg
        return $baseRate + ($weight * $ratePerKg);
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

    public function transfers()
    {
        return $this->hasMany(OrderTransfer::class);
    }
    public function warehouseOrders()
    {
        return $this->hasMany(WarehouseOrder::class);
    }

    public function distribution()
    {
        return $this->hasOne(OrderDistribution::class);
    }

    public function distributionStaff()
    {
        return $this->hasOneThrough(
            User::class,
            OrderDistribution::class,
            'order_id',
            'id',
            'id',
            'staff_id'
        );
    }

    public function shipper()
    {
        return $this->belongsTo(Shipper::class);
    }

    public function getCurrentDistributionAttribute()
    {
        return $this->distributions()
            ->where('status', '!=', 'completed')
            ->latest()
            ->first();
    }

    public function getCurrentDistributorAttribute()
    {
        $distribution = $this->currentDistribution;
        if (!$distribution) return null;

        if ($distribution->type === 'delivery') {
            return $distribution->shipper;
        }
        return $distribution->staff;
    }

    public function shipperRating()
    {
        return $this->hasOne(ShipperRating::class);
    }
}