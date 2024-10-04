<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
<<<<<<< HEAD
use App\Services\OrderAssignmentService;
=======
use Illuminate\Support\Facades\Log;  // Thêm dòng này
use App\Services\OrderAssignmentService;
use Pusher\Pusher;
>>>>>>> 0a21cfa (update 04/10)

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
    }

    public function boot()
    {
        if ($this->app->environment('local')) {
            URL::forceScheme('http');
        }
    
        // Lấy instance của Pusher từ container và đặt logger
        try {
            $pusher = $this->app->make('Pusher');
            $pusher->setLogger(Log::getLogger());
            Log::info('Đã thiết lập logger cho Pusher thành công');
        } catch (\Exception $e) {
            Log::error('Lỗi khi thiết lập logger cho Pusher: ' . $e->getMessage());
        }
    }
}