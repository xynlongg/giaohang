<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\OrderAssignmentService;
use Illuminate\Support\Facades\View; 
use App\Services\WarehouseDispatchService; 

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Service cũ
        $this->app->singleton(OrderAssignmentService::class, function ($app) {
            return new OrderAssignmentService();
        });
        
        // Thêm WarehouseDispatchService
        $this->app->singleton(WarehouseDispatchService::class, function ($app) {
            return new WarehouseDispatchService();
        });
    }

    public function boot()
    {
        if($this->app->environment('local')) {
            URL::forceScheme('http');
        }
        View::composer('*', function ($view) {
            $view->with('helpers', [
                'getRoleBadgeColor' => function ($role) {
                    return match ($role) {
                        'warehouse_manager' => 'primary',
                        'warehouse_staff' => 'info',
                        'warehouse_local_distributor' => 'success',
                        'warehouse_remote_distributor' => 'warning',
                        default => 'secondary'
                    };
                },
                'getRoleDisplayName' => function ($role) {
                    return match ($role) {
                        'warehouse_manager' => 'Quản lý kho',
                        'warehouse_staff' => 'Nhân viên kho',
                        'warehouse_local_distributor' => 'NV phân phối nội thành',
                        'warehouse_remote_distributor' => 'NV phân phối ngoại thành',
                        default => 'Chưa phân quyền'
                    };
                }
            ]);
        });
    }
}