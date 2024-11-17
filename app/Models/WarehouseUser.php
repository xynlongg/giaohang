<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseUser extends Model
{
    protected $fillable = [
        'user_id',
        'warehouse_id',
        'staff_code',
        'start_date',
        'end_date',
        'is_manager',
        'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_manager' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(ProvincialWarehouse::class, 'warehouse_id');
    }
}