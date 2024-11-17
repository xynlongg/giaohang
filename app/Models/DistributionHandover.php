<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DistributionHandover extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_ASSIGNED = 'assigned'; // Thêm trạng thái mới nếu cần

    
    // Định nghĩa các hằng số cho shipping_type
    const SHIPPING_TYPE_LOCAL = 'noi_thanh';
    const SHIPPING_TYPE_REMOTE = 'ngoai_thanh';
    
    protected $fillable = [
        'order_id',
        'distribution_staff_id',
        'origin_post_office_id',
        'destination_post_office_id',
        'destination_warehouse_id',
        'shipping_type',
        'status',
        'distance',
        'assigned_at',
        'completed_at'
    ];

    protected $attributes = [
        'origin_post_office_id' => 0,
        'destination_post_office_id' => 0,
        'distance' => 0,
        'status' => self::STATUS_PENDING // Giá trị mặc định
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public static $allowedStatuses = [
        self::STATUS_PENDING,
        self::STATUS_IN_TRANSIT,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_ASSIGNED // Thêm vào danh sách trạng thái cho phép

    ];

    public static $allowedShippingTypes = [
        self::SHIPPING_TYPE_LOCAL,
        self::SHIPPING_TYPE_REMOTE
    ];

    // Relationships không thay đổi
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function distributionStaff()
    {
        return $this->belongsTo(User::class, 'distribution_staff_id');
    }

    public function originPostOffice()
    {
        return $this->belongsTo(PostOffice::class, 'origin_post_office_id');
    }

    public function destinationPostOffice()
    {
        return $this->belongsTo(PostOffice::class, 'destination_post_office_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(ProvincialWarehouse::class, 'destination_warehouse_id');
    }
}