<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\OrderAssignmentService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderAssignmentService::class, function ($app) {
            return new OrderAssignmentService();
        });
    }

    public function boot()
    {
        if($this->app->environment('local')) {
            URL::forceScheme('http');
        }
    }
}