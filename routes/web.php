<?php

use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\AppFileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryOperationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TagController;

use Illuminate\Support\Facades\Route;

require_once __DIR__.'/rony/items_module.php';
require_once __DIR__.'/rony/receive_return_module.php';

Route::middleware('auth:employee')->group(function (): void {
    Route::get('/', [ModuleController::class, 'index'])->name('modules.index');
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.list');

    Route::get('/inventory/operations', [InventoryOperationController::class, 'index'])->name('inventory.operations');
    Route::post('/inventory/receiving', [InventoryOperationController::class, 'storeReceiving'])->name('inventory.receiving.store');
    Route::post('/inventory/return', [InventoryOperationController::class, 'storeReturn'])->name('inventory.return.store');
    Route::post('/inventory/transfer-out', [InventoryOperationController::class, 'storeTransferOut'])->name('inventory.transfer-out.store');

    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('/sales/categories', [SalesController::class, 'categories'])->name('sales.categories');
    Route::get('/sales/search', [SalesController::class, 'search'])->name('sales.search');
    Route::post('/sales/item', [SalesController::class, 'addItem'])->name('sales.item.add');
    Route::post('/sales/item/{index}', [SalesController::class, 'editItem'])->name('sales.item.edit');
    Route::delete('/sales/item/{index}', [SalesController::class, 'removeItem'])->name('sales.item.remove');
    Route::post('/sales/customer', [SalesController::class, 'setCustomer'])->name('sales.customer.set');
    Route::post('/sales/location', [SalesController::class, 'setLocation'])->name('sales.location.set');
    Route::post('/sales/payment', [SalesController::class, 'addPayment'])->name('sales.payment.add');
    Route::delete('/sales/payment/{index}', [SalesController::class, 'removePayment'])->name('sales.payment.remove');
    Route::post('/sales/complete', [SalesController::class, 'complete'])->name('sales.complete');
    Route::post('/sales/cancel', [SalesController::class, 'cancel'])->name('sales.cancel');
    Route::post('/sales', [SalesController::class, 'complete'])->name('sales.store');
    Route::get('/sales/{sale}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');
    Route::post('/sales/{sale}/return', [SalesController::class, 'returnItems'])->name('sales.return');
    Route::get('/sales/settings/receipt', [SalesController::class, 'settings'])->name('sales.settings');
    Route::post('/sales/settings/receipt', [SalesController::class, 'saveSettings'])->name('sales.settings.save');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{receiverRowId}/read', [MessageController::class, 'markRead'])->name('messages.read');
    Route::delete('/messages/{messageId}', [MessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
    Route::post('/config', [ConfigController::class, 'update'])->name('config.update');

    Route::get('/app_files/view/{fileId}', [AppFileController::class, 'view'])->name('app_files.view');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{personId}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{personId}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{personId}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('/customers/files/{fileId}/download', [CustomerController::class, 'downloadFile'])->name('customers.files.download');
    Route::delete('/customers/files/{fileId}', [CustomerController::class, 'deleteFile'])->name('customers.files.delete');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{personId}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{personId}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{personId}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::get('/suppliers/files/{fileId}/download', [SupplierController::class, 'downloadFile'])->name('suppliers.files.download');
    Route::delete('/suppliers/files/{fileId}', [SupplierController::class, 'deleteFile'])->name('suppliers.files.delete');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employeeId}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employeeId}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employeeId}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('employee.logout');
});

Route::middleware('guest:employee')->group(function (): void {
    Route::get('/login', [EmployeeAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EmployeeAuthController::class, 'login'])->name('login.attempt');
});

Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::get('/tags/create', [TagController::class, 'create'])->name('tags.create');
Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
Route::get('/tags/{id}/edit', [TagController::class, 'edit'])->name('tags.edit');
Route::put('/tags/{id}', [TagController::class, 'update'])->name('tags.update');
Route::delete('/tags/{id}', [TagController::class, 'destroy'])->name('tags.destroy');
Route::get('/tags/list', [TagController::class, 'tagList'])->name('tags.list');
