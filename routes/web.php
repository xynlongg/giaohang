<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShipperRegistrationController;
use App\Http\Controllers\ShipperApprovalController;
use App\Http\Controllers\PostOfficeController;
use App\Http\Controllers\OrderStatusLogController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\WarrantyPackageController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ShipperProfileController;

// Authentication Routes
Route::middleware(['web'])->group(function () {
    Auth::routes();
});

Route::get('/dashboard', function () {
    Log::info('Dashboard route called. User ID: ' . auth()->id());
    return view('dashboard');
})->middleware('auth')->name('dashboard');
// Public Routes
Route::get('/register-shipper', [ShipperProfileController::class, 'register'])->name('register.shipper');
Route::post('/register-shipper', [ShipperProfileController::class, 'storeRegistration'])->name('register.shipper.store');
Route::view('/dashboard', 'dashboard')->name('dashboard');
// Routes for authenticated users
Route::middleware(['auth'])->group(function () {
    // Dashboard Routes
    Route::view('/home', 'dashboard')->name('dashboard');
    Route::view('/', 'dashboard')->name('dashboard');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    
    // User Profile Routes
    Route::get('/users/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::put('/users/profile/updateProfile', [UserController::class, 'updateProfile'])->name('users.updateProfile');
    Route::get('/users/profile/remove-avatar', [UserController::class, 'removeAvatar'])->name('users.removeAvatar');
    
    // Game Route
    Route::view('/game', 'game.show')->name('game.show');
    
    // Chat Routes
    Route::get('/chat', [ChatController::class, 'showChat'])->name('chat.show');
    Route::post('/chat/message', [ChatController::class, 'messageReceived'])->name('chat.message');
    Route::post('/chat/greet/{receiver}', [ChatController::class, 'greetReceived'])->name('chat.greet');
    
    // Order Routes
    Route::get('/orders/import', [OrderController::class, 'showImportForm'])->name('orders.import.form');
    Route::post('/orders/import', [OrderController::class, 'import'])->name('orders.import');
    Route::get('/orders/download-template', [OrderController::class, 'downloadTemplate'])->name('orders.download-template');
    Route::get('/searchOrder', [OrderController::class, 'showSearchForm'])->name('searchOrder.form');
    Route::get('/searchOrder/results', [OrderController::class, 'searchOrder'])->name('searchOrder.results');
    Route::get('/searchByQR', [OrderController::class, 'searchByQR'])->name('orders.searchByQR');
    
    // Shipper Registration Routes
    Route::post('/send-otp', [ShipperRegistrationController::class, 'sendOtp']);
    Route::post('/verify-otp', [ShipperRegistrationController::class, 'verifyOtp']);
    Route::post('/register-shipper', [ShipperRegistrationController::class, 'register']);
    Route::get('/get-cities', [ShipperRegistrationController::class, 'getCities']);
    Route::get('/get-districts/{city}', [ShipperRegistrationController::class, 'getDistricts']);
    
    // Product Routes
    Route::resource('products', ProductController::class);
    
    // Admin Routes
    Route::middleware(['admin'])->group(function () {
        // User and Role Management
        Route::view('/users', 'users.showAll')->name('users.all');
        Route::view('/users/role', 'role.index')->name('role.index');
        Route::view('/users/role/{id}', 'role.assign')->name('role.assign');
        
        // Order Management
        Route::resource('orders', OrderController::class);
        Route::get('/orders/{order}/update', [OrderController::class, 'showUpdateForm'])->name('orders.showUpdateForm');
        
        // Product Category and Warranty Package Management
        Route::resource('product-categories', ProductCategoryController::class);
        Route::resource('warranty-packages', WarrantyPackageController::class);
        
        // Shipper Management
        Route::get('/shippers', [ShipperRegistrationController::class, 'index'])->name('shippers.index');
        Route::get('/shippers/{id}', [ShipperRegistrationController::class, 'show'])->name('shippers.show');
        Route::post('/shippers/{id}/approve', [ShipperRegistrationController::class, 'approve'])->name('shippers.approve');
        Route::post('/shippers/{id}/reject', [ShipperRegistrationController::class, 'reject'])->name('shippers.reject');
        
        // Post Office Management
        Route::resource('post_offices', PostOfficeController::class);
        
        // Order Status Log Management
        Route::resource('orders.status_logs', OrderStatusLogController::class)->except(['create']);
        Route::get('orders/{order}/status_logs', [OrderStatusLogController::class, 'index'])->name('orders.status_logs.index');
    });
});