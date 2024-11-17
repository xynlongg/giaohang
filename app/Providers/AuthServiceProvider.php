<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Order;
use App\Models\WarehouseUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\PersonalAccessToken;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Gate::define('view-warehouse-orders', function (User $user) {
            return $user->roles()
                ->whereIn('name', ['warehouse_staff', 'warehouse_manager', 'admin'])
                ->exists();
        });

        Gate::define('update-warehouse-orders', function (User $user) {
            return $user->roles()
                ->whereIn('name', ['warehouse_staff', 'warehouse_manager', 'admin'])
                ->exists();
        });

        Gate::define('export-warehouse-orders', function (User $user) {
            return $user->roles()
                ->whereIn('name', ['warehouse_manager', 'admin'])
                ->exists();
        });

        // Thêm gate để kiểm tra user có thuộc kho đang xem không
        Gate::define('access-warehouse', function (User $user, $warehouseId = null) {
            $query = WarehouseUser::where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('end_date');
            
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }
            
            return $query->exists();
        });
    }
}