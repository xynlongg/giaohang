<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ShipperProfileController;

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

Route::apiResource('admin/shippers', ShipperProfileController::class);
