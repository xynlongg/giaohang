<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShipperRegistrationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PostOfficeController;
use App\Http\Controllers\OrderStatusLogController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\WarrantyPackageController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PostOfficeStaffController;
use App\Http\Controllers\PostOfficeOrderManagementController;
use App\Http\Controllers\CancellationRequestController;

// Authentication Routes
Auth::routes();
<<<<<<< HEAD

// Public Routes
Route::view('/register-shipper', 'shipper.shipper-registration')->name('shipper.shipper-registration');
Route::get('/web-test', function () {
    return 'Web test successful';
});
=======

// Public Routes
Route::view('/register-shipper', 'shipper.shipper-registration')->name('shipper.shipper-registration');

Route::get('/dispatch-test-job', function () {
    \App\Jobs\TestJob::dispatch();
    return 'Test job dispatched!';
});

>>>>>>> 0a21cfa (update 04/10)
// Home route
Route::get('/', function () {
    if (Auth::check()) {
        return view('dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth'])->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::view('/home', 'dashboard')->name('home');

    Route::post('/logout', [UserController::class, 'logout'])->name('logout');
    Route::get('/my-orders', [OrderController::class, 'customerOrders'])->name('customer.orders');

    // User Profile Routes
    Route::get('/users/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::put('/users/profile/updateProfile', [UserController::class, 'updateProfile'])->name('users.updateProfile');
    Route::get('/users/profile/remove-avatar', [UserController::class, 'removeAvatar'])->name('users.removeAvatar');
    
    // Game Route
    Route::view('/game', 'game.show')->name('game.show');
    Route::view('/orders/import', 'orders.import')->name('orders.import');
    
    // Chat Routes
    Route::get('/chat', [ChatController::class, 'showChat'])->name('chat.show');
    Route::post('/chat/message', [ChatController::class, 'messageReceived'])->name('chat.message');
    Route::post('/chat/greet/{receiver}', [ChatController::class, 'greetReceived'])->name('chat.greet');
    
    // Order Routes
    Route::resource('orders', OrderController::class);
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
    Route::get('/orders/import', [OrderController::class, 'showImportForm'])->name('orders.import');
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

    // Admin and Post Office Manager Routes
    Route::middleware(['role:admin,post_office_manager'])->group(function () {
        // Post Office Manager Routes
        Route::get('/post-office-staff', [PostOfficeStaffController::class, 'index'])->name('post_offices.staff.index');
        Route::post('/post-office-staff/{user}/assign-role', [PostOfficeStaffController::class, 'assignRole'])->name('post_offices.staff.assign_role');
        Route::post('/post-office-staff/{user}/assign-post-office', [PostOfficeStaffController::class, 'assignPostOffice'])->name('post_offices.staff.assign_post_office');
        Route::delete('/post-office-staff/{user}/remove-from-post-office', [PostOfficeStaffController::class, 'removeFromPostOffice'])->name('post_offices.staff.remove_from_post_office');
        Route::get('/post-office/orders', [PostOfficeOrderManagementController::class, 'index'])->name('post_office.orders.index');
        Route::post('/post-office/orders/{order}/assign-shipper', [PostOfficeOrderManagementController::class, 'assignShipper'])->name('post_office.orders.assign_shipper');        
        Route::get('/cancellation-requests', [CancellationRequestController::class, 'index'])->name('cancellation-requests.index');
        Route::get('/cancellation-requests/{cancellationRequest}', [CancellationRequestController::class, 'show'])->name('cancellation-requests.show');
        Route::post('/cancellation-requests/{cancellationRequest}/process', [CancellationRequestController::class, 'process'])->name('cancellation-requests.process');

        // Admin Routes
        Route::middleware(['role:admin'])->group(function () {
            // User and Role Management
            Route::view('/users', 'users.showAll')->name('users.all');
            Route::view('/users/role', 'role.index')->name('role.index');
            Route::view('/users/role/{id}', 'role.assign')->name('role.assign');
            
            // Order Management
            Route::get('/orders/{order}/update', [OrderController::class, 'showUpdateForm'])->name('orders.showUpdateForm');
            Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
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
});

// Redirect all other routes to login if not authenticated
Route::fallback(function () {
    return redirect()->route('login');
<<<<<<< HEAD
});
=======
});
Broadcast::routes();
>>>>>>> 0a21cfa (update 04/10)
