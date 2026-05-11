<?php

use App\Http\Controllers\SalesController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:employee')->group(function (): void {

    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('/sales/categories', [SalesController::class, 'categories'])->name('sales.categories');
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
    Route::get('/sales/{sale}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');
    Route::post('/sales/{sale}/return', [SalesController::class, 'returnItems'])->name('sales.return');
    Route::get('/sales/settings/receipt', [SalesController::class, 'settings'])->name('sales.settings');
    Route::post('/sales/settings/receipt', [SalesController::class, 'saveSettings'])->name('sales.settings.save');

});


Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::get('/tags/create', [TagController::class, 'create'])->name('tags.create');
Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
Route::get('/tags/{id}/edit', [TagController::class, 'edit'])->name('tags.edit');
Route::put('/tags/{id}', [TagController::class, 'update'])->name('tags.update');
Route::delete('/tags/{id}', [TagController::class, 'destroy'])->name('tags.destroy');
Route::get('/tags/list', [TagController::class, 'tagList'])->name('tags.list');
