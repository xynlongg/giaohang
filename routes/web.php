<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShipperRegistrationController;
use App\Http\Controllers\ShipperApprovalController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\PostOfficeController;
use App\Http\Controllers\OrderStatusLogController;



Auth::routes();
Route::get('/register-shipper', [ShipperProfileController::class, 'register'])->name('register.shipper');
Route::post('/register-shipper', [ShipperProfileController::class, 'storeRegistration'])->name('register.shipper.store');


Route::middleware(['auth'])->group(function () {
    Route::view('/home', 'dashboard')->name('dashboard');
    Route::view('/', 'dashboard')->name('dashboard');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/users/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::view('/game', 'game.show')->name('game.show');

    Route::get('/chat', [App\Http\Controllers\ChatController::class, 'showChat'])->name('chat.show');
    Route::post('/chat/message', [App\Http\Controllers\ChatController::class, 'messageReceived'])->name('chat.message');
    Route::post('/chat/greet/{receiver}', [App\Http\Controllers\ChatController::class, 'greetReceived'])->name('chat.greet');

    Route::put('/users/profile/updateProfile', [UserController::class, 'updateProfile'])->name('users.updateProfile');
    Route::get('/users/profile/remove-avatar', [UserController::class, 'removeAvatar'])->name('users.removeAvatar');


    //
    Route::get('/register-shipper', function () {
        return view('shipper.shipper-registration');
    });
    Route::post('/send-otp', [ShipperRegistrationController::class, 'sendOtp']);
    Route::post('/verify-otp', [ShipperRegistrationController::class, 'verifyOtp']);
    Route::post('/register-shipper', [ShipperRegistrationController::class, 'register']);
    
    Route::get('/get-cities', [ShipperRegistrationController::class, 'getCities']);
    Route::get('/get-districts/{city}', [ShipperRegistrationController::class, 'getDistricts']);

    Route::get('/searchOrder', [OrderController::class, 'showSearchForm'])->name('searchOrder.form');
    Route::get('/searchOrder/results', [OrderController::class, 'searchOrder'])->name('searchOrder.results');
    Route::get('/searchByQR', [OrderController::class, 'searchByQR'])->name('orders.searchByQR');
  
    // Các route liên quan đến User và Role chỉ cho admin truy cập
    Route::middleware(['admin'])->group(function () {
        Route::view('/users', 'users.showAll')->name('users.all');
        Route::view('/users/role', 'role.index')->name('role.index');
        Route::view('/users/role/{id}', 'role.assign')->name('role.assign');
        
        
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');  
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

        Route::get('/orders/{order}/update', [OrderController::class, 'showUpdateForm'])->name('orders.showUpdateForm');
        Route::post('/orders/{order}/update', [OrderController::class, 'update'])->name('orders.update');

    });

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/shippers', [ShipperRegistrationController::class, 'index'])->name('shippers.index');
        
        Route::get('/shippers/{id}', [ShipperRegistrationController::class, 'show'])->name('shippers.show');
            
        Route::post('/shippers/{id}/approve', [ShipperRegistrationController::class, 'approve'])->name('shippers.approve');
        Route::post('/shippers/{id}/reject', [ShipperRegistrationController::class, 'reject'])->name('shippers.reject');
        
        Route::resource('post_offices', PostOfficeController::class);
        Route::resource('orders.status_logs', OrderStatusLogController::class)->except(['create']);
        Route::get('orders/{order}/status_logs', [OrderStatusLogController::class, 'index'])->name('orders.status_logs.index');
       

        Route::post('orders/{order}/confirm-arrival', [OrderController::class, 'confirmArrival'])->name('orders.confirm-arrival');
        
        Route::post('/orders/{order}/update-location', [OrderController::class, 'updateLocation'])->name('orders.updateLocation');

    });