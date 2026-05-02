<?php

use App\Http\Controllers\AppFileController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\InventoryOperationController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

require_once __DIR__.'/rony/items_module.php';
require_once __DIR__.'/rony/receive_return_module.php';
require_once __DIR__.'/rony/people_modules.php';
require_once __DIR__.'/rony/sales_inventory_modules.php';

Route::middleware('auth:employee')->group(function (): void {
    Route::get('/', [ModuleController::class, 'index'])->name('modules.index');

    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.list');
    
    Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
    Route::post('/config', [ConfigController::class, 'update'])->name('config.update');

    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class,'index'])->name('reports.index');
        Route::get('/generate/{report}', [ReportController::class,'generate'])->name('reports.generate');
        Route::post('/generate/{report}', [ReportController::class,'store'])->name('reports.store');
    });

    Route::get('/inventory/operations', [InventoryOperationController::class, 'index'])->name('inventory.operations');
    Route::post('/inventory/receiving', [InventoryOperationController::class, 'storeReceiving'])->name('inventory.receiving.store');
    Route::post('/inventory/return', [InventoryOperationController::class, 'storeReturn'])->name('inventory.return.store');
    Route::post('/inventory/transfer-out', [InventoryOperationController::class, 'storeTransferOut'])->name('inventory.transfer-out.store');

   
    Route::get('/app_files/view/{fileId}', [AppFileController::class, 'view'])->name('app_files.view');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('employee.logout');
});

Route::middleware('guest:employee')->group(function (): void {
    Route::get('/login', [EmployeeAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EmployeeAuthController::class, 'login'])->name('login.attempt');
});
