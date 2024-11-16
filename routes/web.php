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
use App\Http\Controllers\ProvincialWarehouseController;
use App\Http\Controllers\ProvincialWarehouseStaffController;
use App\Http\Controllers\DistributionStaffController;
use App\Http\Controllers\OrderDistributionController;
use App\Http\Controllers\PostOfficeShipmentReceivingController;
use App\Http\Controllers\CompletedOrdersController;
use App\Http\Controllers\WarehouseOrderManagementController;
use App\Http\Controllers\DistributorAssignedOrdersController;
use App\Http\Controllers\WarehouseDistributorOrdersController;
// Authentication Routes
Auth::routes();

// Public Routes
Route::view('/register-shipper', 'shipper.shipper-registration')->name('shipper.shipper-registration');



Route::get('/web-test', function () {
    return 'Web test successful';
});
Route::get('/get-cities', [ShipperRegistrationController::class, 'getCities']);
    Route::get('/get-districts/{city}', [ShipperRegistrationController::class, 'getDistricts']);
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
    Route::get('/orders/completed', [CompletedOrdersController::class, 'index'])->name('orders.completed');
    Route::post('/orders/{order}/rate', [CompletedOrdersController::class, 'rateShipper'])->name('orders.rate');

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
    
   
    // Product Routes
    Route::resource('products', ProductController::class);

    // để ké
    Route::post('distribution/batch-update-arrival', [DistributionStaffController::class, 'batchUpdateArrival'])
    ->name('distribution.batch-update-arrival');

    // Admin and Post Office Manager Routes
    Route::middleware(['role:admin,post_office_manager'])->group(function () {
        // Post Office Manager Routes
        Route::get('/post-office-staff', [PostOfficeStaffController::class, 'index'])->name('post_offices.staff.index');
        Route::post('/post-office-staff/{user}/assign-role', [PostOfficeStaffController::class, 'assignRole'])->name('post_offices.staff.assign_role');
        Route::post('/post-office-staff/{user}/assign-post-office', [PostOfficeStaffController::class, 'assignPostOffice'])->name('post_offices.staff.assign_post_office');
        Route::delete('/post-office-staff/{user}/remove-from-post-office', [PostOfficeStaffController::class, 'removeFromPostOffice'])->name('post_offices.staff.remove_from_post_office');
        //orders 
        Route::get('/post-office/orders', [PostOfficeOrderManagementController::class, 'index'])->name('post_office.orders.index');
        Route::post('/post-office/orders/{order}/assign-shipper', [PostOfficeOrderManagementController::class, 'assignShipper'])->name('post_office.orders.assign_shipper');        
        Route::get('/post-office/orders/prepared', [PostOfficeOrderManagementController::class, 'managePreparedOrders'])->name('post_office.orders.manage_prepared');
        Route::post('/post-office/orders/{order}/assign_prepared', [PostOfficeOrderManagementController::class, 'assignShipperToPreparedOrder'])->name('post_office.orders.assign_shipper_prepared');
        Route::post('/post-office/orders/smart-sorting', [PostOfficeOrderManagementController::class, 'processSmartSorting'])->name('post_office.orders.smart_sorting');
        Route::post('/post-office/orders/bulk-dispatch', [PostOfficeOrderManagementController::class, 'bulkDispatch'])->name('post_office.orders.bulk_dispatch');
        Route::get('/post-office/orders/get-distribution-staff', [PostOfficeOrderManagementController::class, 'getDistributionStaff'])
        ->name('post_office.orders.get_distribution_staff');

        //xác nhận đơn hàng sau bàn giao 

        Route::prefix('/post-office/receiving')->name('post_office.receiving.')->group(function () {
            Route::get('/', [PostOfficeShipmentReceivingController::class, 'index'])
                ->name('index');
            Route::post('/confirm', [PostOfficeShipmentReceivingController::class, 'confirmReceiptAndAssignShipper'])
                ->name('confirm');
                Route::post('/confirm-arrival', [PostOfficeShipmentReceivingController::class, 'confirmArrival'])
                ->name('confirm_arrival');
            
            Route::post('/assign-shipper', [PostOfficeShipmentReceivingController::class, 'assignShipper'])
                ->name('assign_shipper');
                Route::post('/orders/{order}/start-delivery', [PostOfficeShipmentReceivingController::class, 'startDelivery'])
                ->name('start_delivery');
            Route::get('/assigned-orders', [PostOfficeShipmentReceivingController::class, 'getAssignedOrders'])
                ->name('assigned_orders');
        });
       
        
        //cancellation 

        Route::get('/cancellation-requests', [CancellationRequestController::class, 'index'])->name('cancellation-requests.index');
        Route::get('/cancellation-requests/{cancellationRequest}', [CancellationRequestController::class, 'show'])->name('cancellation-requests.show');
        Route::post('/cancellation-requests/{cancellationRequest}/process', [CancellationRequestController::class, 'process'])->name('cancellation-requests.process');

        Route::post('/post-office/orders/bulk-dispatch-to-warehouse', [PostOfficeOrderManagementController::class, 'bulkDispatchToProvincialWarehouse'])
        ->name('post_office.orders.bulk_dispatch_to_warehouse');
        Route::post('/post-office/orders/dispatch-to-local', [PostOfficeOrderManagementController::class, 'dispatchToLocalPostOffice'])
        ->name('post_office.orders.dispatch_to_local');
        Route::post('/post-office/orders/dispatch-single-to-warehouse', [PostOfficeOrderManagementController::class, 'dispatchSingleToWarehouse'])->name('post_office.orders.dispatch_single_to_warehouse');
        // Admin Routes
         // Shipper Management
         Route::get('/shippers', [ShipperRegistrationController::class, 'index'])->name('shippers.index');
         Route::get('/shippers/{id}', [ShipperRegistrationController::class, 'show'])->name('shippers.show');
         Route::post('/shippers/{id}/approve', [ShipperRegistrationController::class, 'approve'])->name('shippers.approve');
         Route::post('/shippers/{id}/reject', [ShipperRegistrationController::class, 'reject'])->name('shippers.reject');
         
        Route::middleware(['role:admin'])->group(function () {
            // User and Role Management
            Route::view('/users', 'users.showAll')->name('users.all');
            Route::view('/users/role', 'role.index')->name('role.index');
            Route::view('/users/role/{id}', 'role.assign')->name('role.assign');
            
            // Order Management
            Route::get('/orders/{order}/update', [OrderController::class, 'showUpdateForm'])->name('orders.showUpdateForm');
            
            // Product Category and Warranty Package Management
            Route::resource('product-categories', ProductCategoryController::class);
            Route::resource('warranty-packages', WarrantyPackageController::class);
           
            // Post Office Management
            Route::resource('post_offices', PostOfficeController::class);
            
            // Order Status Log Management
            Route::resource('orders.status_logs', OrderStatusLogController::class)->except(['create']);
            Route::get('orders/{order}/status_logs', [OrderStatusLogController::class, 'index'])->name('orders.status_logs.index');

           
            
        });
    });
});



Route::middleware(['auth'])->group(function () {

    Route::get('/warehouse/orders', [WarehouseOrderManagementController::class, 'index'])
    ->name('warehouse.orders.index');

    
    Route::get('/warehouse/orders/assigned', [WarehouseOrderManagementController::class, 'getAssignedOrders'])
        ->name('warehouse.orders.get_assigned');
        
    Route::get('/warehouse/orders/{warehouseOrder}', [WarehouseOrderManagementController::class, 'show'])
        ->name('warehouse.orders.show');

    Route::patch('/warehouse/orders/{warehouseOrder}', [WarehouseOrderManagementController::class, 'update'])
        ->name('warehouse.orders.update');

    Route::post('/warehouse/orders/assign-distributor', [WarehouseOrderManagementController::class, 'assignDistributor'])
        ->name('warehouse.orders.assign_distributor');
    
    Route::post('/warehouse/orders/{id}/confirm', [WarehouseOrderManagementController::class, 'confirmArrival'])
        ->name('warehouse.orders.confirm_arrival');
    Route::post('/warehouse/orders/confirm-bulk', [WarehouseOrderManagementController::class, 'confirmBulkArrival'])
        ->name('warehouse.orders.confirm_bulk_arrival');

       // Danh sách đơn hàng đang phân phối

    Route::prefix('distribution')->name('distribution.')->group(function () {
        // Danh sách đơn hàng đang phân phối
        Route::get('/orders', [OrderDistributionController::class, 'index'])
            ->name('orders.index');
        
        // Cập nhật hàng loạt trạng thái
        Route::post('/batch-update', [OrderDistributionController::class, 'batchUpdate'])
            ->name('batch-update');
        
        // Cập nhật từng đơn riêng lẻ
        Route::post('/{handover}/update', [OrderDistributionController::class, 'updateSingleArrival'])
            ->name('update-arrival');
    });
    //kho tổng 
    Route::prefix('provincial-warehouses')->name('provincial-warehouses.')->group(function () {
        // Route cơ bản - cho tất cả user đã đăng nhập
        Route::get('/', [ProvincialWarehouseController::class, 'index'])->name('index');
        
        // Routes cho admin và warehouse manager
        Route::middleware(['role:admin,warehouse_manager'])->group(function () {
            // CRUD kho tổng - Đặt các route cụ thể trước route có pattern
            Route::get('/create', [ProvincialWarehouseController::class, 'create'])->name('create');
            Route::post('/', [ProvincialWarehouseController::class, 'store'])->name('store');
            
            // Quản lý nhân viên
            Route::get('/staff/search', [ProvincialWarehouseStaffController::class, 'searchStaff'])->name('staff.search');
            Route::post('/staff/{user}/assign-warehouse', [ProvincialWarehouseStaffController::class, 'assignWarehouse'])
                ->name('staff.assign_warehouse');
            Route::delete('/staff/{user}/remove-from-warehouse', [ProvincialWarehouseStaffController::class, 'removeFromWarehouse'])
                ->name('staff.remove_from_warehouse');
            
            Route::get('/orders', [ProvincialWarehouseOrderController::class, 'index'])->name('orders.index');
    
            // Routes có pattern với provincialWarehouse
            Route::get('/{provincialWarehouse}/edit', [ProvincialWarehouseController::class, 'edit'])->name('edit');
            Route::put('/{provincialWarehouse}', [ProvincialWarehouseController::class, 'update'])->name('update');
            Route::delete('/{provincialWarehouse}', [ProvincialWarehouseController::class, 'destroy'])->name('destroy');
        });
    
        // Route show phải đặt cuối cùng vì nó có pattern chung nhất
        Route::get('/{provincialWarehouse}', [ProvincialWarehouseController::class, 'show'])->name('show');
    });
    //phân phối kho tổng
    Route::prefix('distributor/assigned-orders')->name('distributor.assigned-orders')->group(function () {
        Route::get('/', [DistributorAssignedOrdersController::class, 'index'])->name('index');
        Route::post('/{handover}/update-arrival', [DistributorAssignedOrdersController::class, 'updateArrivalStatus'])->name('update-arrival');
        Route::post('/batch-update-arrival', [DistributorAssignedOrdersController::class, 'batchUpdateArrival'])->name('batch-update-arrival');
    });

    Route::middleware(['auth', 'role:warehouse_local_distributor,warehouse_remote_distributor'])
    ->prefix('warehouse/distributor')
    ->name('warehouse.distributor.')
    ->group(function () {
        Route::get('/orders', [WarehouseDistributorOrdersController::class, 'index'])
            ->name('orders.index');
            
        Route::post('/orders/update-local-delivery', [WarehouseDistributorOrdersController::class, 'updateLocalDelivery'])
            ->name('orders.update-local');
            
        Route::post('/orders/update-remote-delivery', [WarehouseDistributorOrdersController::class, 'updateRemoteDelivery'])
            ->name('orders.update-remote');
    });
    
});


// Group các routes cho quản lý kho

// Redirect all other routes to login if not authenticated
Route::fallback(function () {
    return redirect()->route('login');
});