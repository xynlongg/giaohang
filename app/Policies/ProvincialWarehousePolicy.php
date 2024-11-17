<?php

namespace App\Policies;

use App\Models\ProvincialWarehouse;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProvincialWarehousePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasRole('admin') || $user->hasRole('warehouse_manager');
    }

    public function view(User $user, ProvincialWarehouse $provincialWarehouse)
    {
        return $user->hasRole('admin') || $user->hasRole('warehouse_manager') || 
               $user->warehouseStaff && $user->warehouseStaff->provincial_warehouse_id === $provincialWarehouse->id;
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ProvincialWarehouse $provincialWarehouse)
    {
        return $user->hasRole('admin') || 
               ($user->hasRole('warehouse_manager') && $user->warehouseStaff && $user->warehouseStaff->provincial_warehouse_id === $provincialWarehouse->id);
    }

    public function delete(User $user, ProvincialWarehouse $provincialWarehouse)
    {
        return $user->hasRole('admin');
    }

    public function assignStaff(User $user, ProvincialWarehouse $provincialWarehouse)
    {
        return $user->hasRole('admin') || 
               ($user->hasRole('warehouse_manager') && $user->warehouseStaff && $user->warehouseStaff->provincial_warehouse_id === $provincialWarehouse->id);
    }
}