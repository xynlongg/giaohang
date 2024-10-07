<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
<<<<<<< HEAD
<<<<<<< HEAD
use App\Services\OrderAssignmentService;
=======
use Illuminate\Support\Facades\Log;  // Thêm dòng này
use App\Services\OrderAssignmentService;
use Pusher\Pusher;
>>>>>>> 0a21cfa (update 04/10)
=======
use App\Services\OrderAssignmentService;
>>>>>>> 7d3f46b (update realtime redis)

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
<<<<<<< HEAD
<<<<<<< HEAD
        $this->app->singleton(OrderAssignmentService::class, function ($app) {
            return new OrderAssignmentService();
        });
=======
        // Đăng ký singleton cho OrderAssignmentService
        $this->app->singleton(OrderAssignmentService::class, function ($app) {
            return new OrderAssignmentService();
        });

        // Đăng ký singleton cho Pusher
        $this->app->singleton('Pusher', function () {
            return new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_APP_CLUSTER'),
                    'useTLS' => true,
                ]
            );
        });
>>>>>>> 0a21cfa (update 04/10)
=======
        $this->app->singleton(OrderAssignmentService::class, function ($app) {
            return new OrderAssignmentService();
        });
>>>>>>> 7d3f46b (update realtime redis)
    }

    public function boot()
    {
        if($this->app->environment('local')) {
            URL::forceScheme('http');
        }
    }
}