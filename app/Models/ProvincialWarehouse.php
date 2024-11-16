<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class ProvincialWarehouse extends Model
{
    protected $fillable = [
        'name',
        'address',
        'district',
        'province',
        'latitude',
        'longitude',
        'coordinates'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the users associated with the warehouse.
     */
    public function users(): HasMany
    {
        return $this->hasMany(WarehouseUser::class, 'warehouse_id');
    }
    /**
     * Get the orders that are currently at this warehouse.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'current_location_id')
            ->where('current_location_type', ProvincialWarehouse::class);
    }

    /**
     * Get the warehouse orders for this warehouse.
     */
    public function activeUsers()
    {
        return $this->users()
            ->where('is_active', true)
            ->whereNull('end_date');
    }

    public function managers()
    {
        return $this->users()
            ->where('is_manager', true)
            ->where('is_active', true)
            ->whereNull('end_date');
    }
}