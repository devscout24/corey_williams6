<?php

use App\Http\Controllers\AppFileController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LanController;
use App\Http\Controllers\LanStatusController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ZktecoController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/rony/items_module.php';
require __DIR__.'/rony/receive_return_module.php';
require __DIR__.'/rony/people_modules.php';
require __DIR__.'/rony/sales_inventory_modules.php';

Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

Route::middleware('installed')->group(function (): void {
    Route::middleware('auth:employee')->group(function (): void {
        Route::get('/', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.list');

        // Config module
        Route::middleware('check_module_permission:config')->group(function () {
            Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
            Route::post('/config', [ConfigController::class, 'update'])->name('config.update');

            Route::get('/zkteco', [ZktecoController::class, 'index'])->name('zkteco.index');
            Route::get('/zkteco/connect', [ZktecoController::class, 'connect'])->name('zkteco.connect');
            Route::get('/zkteco/attendance', [ZktecoController::class, 'attendance'])->name('zkteco.attendance');
            Route::post('/zkteco/config', [ZktecoController::class, 'saveConfig'])->name('zkteco.save-config');
        });

        // Reports module
        Route::middleware('check_module_permission:reports')->group(function () {
            Route::prefix('reports')->group(function () {
                Route::get('/', [ReportController::class, 'index'])->name('reports.index');
                Route::get('/vat', [ReportController::class, 'vatIndex'])->name('reports.vat');
                Route::get('/generate/{report}', [ReportController::class, 'generate'])->name('reports.generate');
                Route::post('/generate/{report}', [ReportController::class, 'store'])->name('reports.store');
                Route::post('/details/{report}', [ReportController::class, 'getReportDetails'])->name('reports.details');
            });
        });

        // Receivings module (transfers are submodules of receivings)
        Route::middleware('check_module_permission:receivings')->group(function () {
            Route::get('/transfers/out', [TransferController::class, 'outIndex'])->name('transfers.out');
            Route::get('/transfers/in', [TransferController::class, 'inIndex'])->name('transfers.in');
            Route::get('/transfers/new', [TransferController::class, 'create'])->name('transfers.create');
            Route::post('/transfers/item', [TransferController::class, 'addItem'])->name('transfers.item.add');
            Route::post('/transfers/item/{index}', [TransferController::class, 'editItem'])->name('transfers.item.edit');
            Route::delete('/transfers/item/{index}', [TransferController::class, 'removeItem'])->name('transfers.item.remove');
            Route::post('/transfers/location', [TransferController::class, 'setLocation'])->name('transfers.location.set');
            Route::post('/transfers/supplier', [TransferController::class, 'setSupplier'])->name('transfers.supplier.set');
            Route::get('/transfers/categories', [TransferController::class, 'categories'])->name('transfers.categories');
            Route::get('/transfers/search', [TransferController::class, 'search'])->name('transfers.search');
            Route::post('/transfers/complete', [TransferController::class, 'complete'])->name('transfers.complete');
            Route::post('/transfers/save', [TransferController::class, 'save'])->name('transfers.save');
            Route::get('/transfers/edit/{id}', [TransferController::class, 'edit'])->name('transfers.edit');
            Route::post('/transfers/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
            Route::delete('/transfers/bulk-delete', [TransferController::class, 'bulkDelete'])->name('transfers.bulk-delete');
        });

        // Locations module
        Route::middleware('check_module_permission:locations')->group(function () {
            Route::get('/lan/locations', [LanStatusController::class, 'index'])->name('lan.locations');
            Route::post('/lan/locations', [LanStatusController::class, 'store'])->name('lan.locations.store');
            Route::post('/lan/locations/self-name', [LanStatusController::class, 'updateSelfName'])->name('lan.locations.self-name');
            Route::get('/lan/locations/resync-ip/preview', [LanStatusController::class, 'resyncIpPreview'])->name('lan.locations.resync-ip.preview');
            Route::post('/lan/locations/resync-ip', [LanStatusController::class, 'resyncIp'])->name('lan.locations.resync-ip');
            Route::post('/lan/locations/{location}', [LanStatusController::class, 'update'])->name('lan.locations.update');
            Route::post('/lan/locations/{location}/delete', [LanStatusController::class, 'destroy'])->name('lan.locations.destroy');
            Route::post('/lan/locations/{location}/poke', [LanStatusController::class, 'poke'])->name('lan.locations.poke');
            Route::post('/lan/locations/retry/{id}', [LanStatusController::class, 'retry'])->name('lan.locations.retry');
        });

        // Items module (orders are submodules of items)
        Route::middleware('check_module_permission:items')->group(function () {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            Route::get('/orders/pull-list', [OrderController::class, 'pullList'])->name('orders.pull-list');
            Route::get('/orders/search-items', [OrderController::class, 'searchItems'])->name('orders.search-items');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
            Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
            Route::get('/orders/export/csv', [OrderController::class, 'export'])->name('orders.export');
            Route::get('/orders/export/xls', [OrderController::class, 'exportXls'])->name('orders.export-xls');
            Route::post('/orders/{order}/close', [OrderController::class, 'close'])->name('orders.close');
            Route::get('/orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
            Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
        });

        // App notifications and user files (accessible to all authenticated employees)
        Route::get('/app/notifications', [LanController::class, 'appNotifications'])->name('app.notifications');
        Route::get('/app/notifications/all', [LanController::class, 'allNotifications'])->name('app.notifications.all');
        Route::post('/app/notifications/{id}/read', [LanController::class, 'readNotification'])->name('app.notifications.read');
        Route::delete('/app/notifications/{id}', [LanController::class, 'deleteNotification'])->name('app.notifications.delete');
        Route::get('/app_files/view/{fileId}', [AppFileController::class, 'view'])->name('app_files.view');

        // Personal profile and logout (accessible to all authenticated employees)
        Route::get('/profile', [EmployeeController::class, 'profile'])->name('employee.profile');
        Route::post('/profile', [EmployeeController::class, 'updateProfile'])->name('employee.profile.update');
        Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('employee.logout');
    });

    Route::middleware('guest:employee')->group(function (): void {
        Route::get('/login', [EmployeeAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [EmployeeAuthController::class, 'login'])->name('login.attempt');
    });
});
