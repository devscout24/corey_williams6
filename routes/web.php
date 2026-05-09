<?php

use App\Http\Controllers\AppFileController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\TransferController;
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

    Route::get('/transfers/out', [TransferController::class, 'outIndex'])->name('transfers.out');
    Route::get('/transfers/in', [TransferController::class, 'inIndex'])->name('transfers.in');
    Route::get('/transfers/new', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers/item', [TransferController::class, 'addItem'])->name('transfers.item.add');
    Route::post('/transfers/item/{index}', [TransferController::class, 'editItem'])->name('transfers.item.edit');
    Route::delete('/transfers/item/{index}', [TransferController::class, 'removeItem'])->name('transfers.item.remove');
    Route::post('/transfers/location', [TransferController::class, 'setLocation'])->name('transfers.location.set');
    Route::post('/transfers/complete', [TransferController::class, 'complete'])->name('transfers.complete');
    Route::post('/transfers/save', [TransferController::class, 'save'])->name('transfers.save');
    Route::get('/transfers/edit/{id}', [TransferController::class, 'edit'])->name('transfers.edit');
    Route::post('/transfers/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');

   
    Route::get('/app_files/view/{fileId}', [AppFileController::class, 'view'])->name('app_files.view');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/pull-list', [OrderController::class, 'pullList'])->name('orders.pull-list');
    Route::get('/orders/search-items', [OrderController::class, 'searchItems'])->name('orders.search-items');
    Route::get('/orders/{receivingId}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{receivingId}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::post('/orders/{receivingId}/approve', [OrderController::class, 'approve'])->name('orders.approve');
    Route::get('/orders/{receivingId}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::delete('/orders/{receivingId}', [OrderController::class, 'destroy'])->name('orders.destroy');

    Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('employee.logout');
});

Route::middleware('guest:employee')->group(function (): void {
    Route::get('/login', [EmployeeAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EmployeeAuthController::class, 'login'])->name('login.attempt');
});
