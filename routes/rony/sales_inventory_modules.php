<?php

use App\Http\Controllers\SalesController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:employee', 'check_module_permission:sales'])->group(function (): void {

    // Register open/change routes (bypass the check_register_open middleware)
    Route::get('/sales/register/open', [SalesController::class, 'showRegisterOpenForm'])->name('sales.register.open');
    Route::post('/sales/register/open', [SalesController::class, 'openRegister'])->name('sales.register.open.post');
    Route::post('/sales/register/change', [SalesController::class, 'changeRegister'])->name('sales.register.change');

    // Sales register routes that require the register to be open
    Route::middleware('check_register_open')->group(function () {
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/categories', [SalesController::class, 'categories'])->name('sales.categories');
        Route::get('/sales/tags', [SalesController::class, 'tags'])->name('sales.tags');
        Route::get('/sales/tags/{tagId}/items', [SalesController::class, 'tagItems'])->name('sales.tags.items');
        Route::get('/sales/favorites', [SalesController::class, 'favorites'])->name('sales.favorites');
        Route::get('/sales/search', [SalesController::class, 'search'])->name('sales.search');
        Route::post('/sales/item', [SalesController::class, 'addItem'])->name('sales.item.add');
        Route::post('/sales/item/{index}', [SalesController::class, 'editItem'])->name('sales.item.edit');
        Route::delete('/sales/item/{index}', [SalesController::class, 'removeItem'])->name('sales.item.remove');
        Route::post('/sales/customer', [SalesController::class, 'setCustomer'])->name('sales.customer.set');
        Route::post('/sales/supplier', [SalesController::class, 'setSupplier'])->name('sales.supplier.set');
        Route::post('/sales/location', [SalesController::class, 'setLocation'])->name('sales.location.set');
        Route::post('/sales/payment', [SalesController::class, 'addPayment'])->name('sales.payment.add');
        Route::delete('/sales/payment/{index}', [SalesController::class, 'removePayment'])->name('sales.payment.remove');
        Route::post('/sales/complete', [SalesController::class, 'complete'])->name('sales.complete');
        Route::post('/sales/cancel', [SalesController::class, 'cancel'])->name('sales.cancel');
        Route::post('/sales', [SalesController::class, 'complete'])->name('sales.store');
        Route::get('/sales/settings/receipt', [SalesController::class, 'settings'])->name('sales.settings');
        Route::post('/sales/settings/receipt', [SalesController::class, 'saveSettings'])->name('sales.settings.save');
        Route::get('/sales/{sale}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');
        Route::post('/sales/{sale}/return', [SalesController::class, 'returnItems'])->name('sales.return');
        
        Route::get('/sales/register/close', [SalesController::class, 'showRegisterCloseForm'])->name('sales.register.close');
        Route::post('/sales/register/close', [SalesController::class, 'closeRegister'])->name('sales.register.close.post');
        Route::post('/sales/register/blind-close', [SalesController::class, 'blindCloseRegister'])->name('sales.register.blind-close');
    });
});

Route::middleware(['auth:employee', 'check_module_permission:reconciliation'])->group(function (): void {
    // Reconciliation routes (outside check_register_open — register is already closed)
    Route::get('/registers/reconciliation', [SalesController::class, 'reconciliationIndex'])->name('registers.reconciliation.index');
    Route::get('/registers/reconciliation/{log}', [SalesController::class, 'reconciliationEdit'])->name('registers.reconciliation.edit');
    Route::put('/registers/reconciliation/{log}', [SalesController::class, 'reconciliationUpdate'])->name('registers.reconciliation.update');
});

Route::middleware(['auth:employee', 'check_module_permission:items'])->group(function (): void {
    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [TagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{id}/edit', [TagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{id}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{id}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::get('/tags/list', [TagController::class, 'tagList'])->name('tags.list');
});
