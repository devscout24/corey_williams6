<?php

use App\Http\Controllers\ReceivingController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:employee')->group(function (): void {
    Route::get('/purchases', [ReceivingController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [ReceivingController::class, 'create'])->name('purchases.create');

    // receivings (legacy URLs; same handlers as purchases)
    Route::get('/receivings', [ReceivingController::class, 'index'])->name('receivings.index');
    Route::get('/receivings/create', [ReceivingController::class, 'create'])->name('receivings.create');
    Route::get('/receivings/categories', [ReceivingController::class, 'categories'])->name('receivings.categories');
    Route::get('/receivings/search', [ReceivingController::class, 'search'])->name('receivings.search');
    Route::post('/receivings/item', [ReceivingController::class, 'addItem'])->name('receivings.item.add');
    Route::post('/receivings/item/{index}', [ReceivingController::class, 'editItem'])->name('receivings.item.edit');
    Route::delete('/receivings/item/{index}', [ReceivingController::class, 'removeItem'])->name('receivings.item.remove');
    Route::post('/receivings/supplier', [ReceivingController::class, 'setSupplier'])->name('receivings.supplier.set');
    Route::post('/receivings/mode', [ReceivingController::class, 'setMode'])->name('receivings.mode.set');
    Route::post('/receivings/complete', [ReceivingController::class, 'complete'])->name('receivings.complete');
    Route::post('/receivings/cancel', [ReceivingController::class, 'cancel'])->name('receivings.cancel');
    Route::post('/receivings/sync-transfer', [ReceivingController::class, 'syncTransfer'])->name('receivings.sync-transfer');

    // returns
});