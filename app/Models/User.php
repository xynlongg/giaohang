<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Events\UserCreated;
use App\Events\UserUpdated;
use App\Events\UserDeleted;
use App\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use App\Models\ShipperProfile;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Notifiable, HasRoles;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'email_verified_at',
        'password',
        'remember_token',
        
        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The events to dispatch on model actions.
     *
     * @var array<string, string>
     */
    protected $dispatchesEvents = [
        'created' => UserCreated::class,
        'updated' => UserUpdated::class,
        'deleted' => UserDeleted::class,
    ];

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Get the deliveries for the user (as a shipper).
     */
    public function shipperProfile()
    {
        return $this->hasOne(ShipperProfile::class);
    }
   
    public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            return $this->roles()->whereIn('name', $roles)->exists();
        }
        
        return $this->roles()->where('name', $roles)->exists();
    }
 
    public function postOffices()
    {
        return $this->belongsToMany(PostOffice::class, 'post_office_user');
    }
    
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        if (!$this->hasRole($role->name)) {
            $this->roles()->attach($role->id);
        }
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        $this->roles()->detach($role->id);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }
    public function warehouses(): HasMany
    {
        return $this->hasMany(WarehouseUser::class);
    }

    public function handledOrders(): HasMany
    {
        return $this->hasMany(OrderHandler::class);
    }

    public function getCurrentWarehouse()
    {
        return $this->warehouses()
            ->where('is_active', true)
            ->whereNull('end_date')
            ->first();
    }
    public function warehouseUsers()
    {
        return $this->hasMany(WarehouseUser::class);
    }

    public function currentWarehouse()
    {
        return $this->hasOne(WarehouseUser::class)
            ->where('is_active', true)
            ->whereNull('end_date');
    }
    public function syncRoles(array $roles)
    {
        return $this->roles()->sync(
            Role::whereIn('name', $roles)->pluck('id')
        );
    }
    /**
     * Lấy các đơn hàng được phân phối cho nhân viên
     */
    public function distributions()
    {
        return $this->hasMany(OrderDistribution::class, 'staff_id');
    }

    /**
     * Lấy các đơn hàng đang xử lý
     */
    public function activeDistributions()
    {
        return $this->hasMany(OrderDistribution::class, 'staff_id')
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['pending', 'processing']);
            });
    }
    public function activeHandovers()
    {
        return $this->hasMany(DistributionHandover::class, 'distribution_staff_id')
            ->where('status', 'in_progress');
    }
    
   
}
