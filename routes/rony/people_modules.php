<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:employee')->group(function (): void {
    
    // column prefs
    Route::post('/customers/columns/save', [\App\Http\Controllers\InventoryColumnSettingController::class, 'saveColumnPrefs'])->name('customers.save_column_prefs');
    Route::get('/customers/columns/reset', [\App\Http\Controllers\InventoryColumnSettingController::class, 'resetColumnPrefs'])->name('customers.reset_column_prefs');
    Route::post('/suppliers/columns/save', [\App\Http\Controllers\InventoryColumnSettingController::class, 'saveColumnPrefs'])->name('suppliers.save_column_prefs');
    Route::get('/suppliers/columns/reset', [\App\Http\Controllers\InventoryColumnSettingController::class, 'resetColumnPrefs'])->name('suppliers.reset_column_prefs');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{receiverRowId}/read', [MessageController::class, 'markRead'])->name('messages.read');
    Route::delete('/messages/{messageId}', [MessageController::class, 'destroy'])->name('messages.destroy');

    
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

});