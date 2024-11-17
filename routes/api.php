<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ShipperOrderController;
use App\Http\Controllers\ShipperProfileController;
use App\Http\Controllers\ShipperAuthController;
use App\Http\Controllers\ShipperController;
use App\Http\Controllers\PostOfficeOrderManagementController;
use Illuminate\Support\Facades\Log;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

// Các route liên quan đến User
Route::apiResource('users', UserController::class);
Route::get('/user/{id}', [UserController::class, 'getUserWithRoles']);
Route::get('/users', [UserController::class, 'index']);

// Các route liên quan đến Role
Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'store']);
Route::put('/roles/{role}', [RoleController::class, 'update']);
Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

// Route để phân quyền cho User
Route::post('/user/{userId}/assign-role', [RoleController::class, 'assignRole']);
Route::delete('/user/{userId}/remove-role', [RoleController::class, 'removeRole']);

// Lấy danh sách vai trò hiện tại của người dùng
Route::get('/user/{userId}/roles', [RoleController::class, 'getUserRoles']);


// Shipper
Route::apiResource('admin/shippers', ShipperProfileController::class);
Route::post('/shipper/login', [ShipperAuthController::class, 'login'])->middleware('throttle:login');
Route::post('/shipper/forgot-password', [ShipperAuthController::class, 'forgotPassword']);
Route::post('/shipper/reset-password', [ShipperAuthController::class, 'resetPassword'])->name('shipper.password.reset');

Route::middleware('auth.shipper')->group(function () {
    Route::get('/check-image/{imageName}', [ShipperOrderController::class, 'checkImageExists']);
    Route::get('/shipper/currentshipper', [ShipperAuthController::class, 'getCurrentShipper']);
    Route::get('/shipper/me', [ShipperAuthController::class, 'profile']);
    Route::post('/shipper/logout', [ShipperAuthController::class, 'logout']);
    Route::post('/shipper/avatar', [ShipperController::class, 'uploadAvatar']);
    Route::put('/shipper/profile', [ShipperController::class, 'updateProfile']);
    Route::post('/shipper/change-password', [ShipperAuthController::class, 'changePassword']);

    Route::get('/orders/{orderId}/available-shippers', [PostOfficeOrderManagementController::class, 'getAvailableShippers']);
    Route::get('/shipper/orders', [ShipperOrderController::class, 'getAssignedOrders']);
    Route::post('/shipper/orders/{order}/status', [ShipperOrderController::class, 'updateOrderStatus']);
    Route::get('/shipper/orders/{order}/post-office', [ShipperOrderController::class, 'getPostOffice']);
    Route::get('/shipper/orders/{id}', [ShipperOrderController::class, 'getOrderDetail']);
    Route::get('shipper/deliveryorders', [ShipperOrderController::class, 'getDeliveryOrders'])
    ->middleware('auth:shipper');
    Route::get('shipper/deliveryorders/{id}', [ShipperOrderController::class, 'getDeliveryOrderDetail']);
    Route::post('shipper/deliveryorders/{id}/status', [ShipperOrderController::class, 'updateDeliveryOrderStatus']);

});




