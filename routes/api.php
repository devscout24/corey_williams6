<?php

use App\Http\Controllers\LanController;
use Illuminate\Support\Facades\Route;

Route::prefix('lan')->middleware('sync.auth')->group(function (): void {
    Route::post('/announce', [LanController::class, 'announce']);
    Route::post('/poke', [LanController::class, 'pokeReceived']);
    Route::post('/sync', [LanController::class, 'sync']);
    Route::post('/receive', [LanController::class, 'receive']);
    Route::post('/transfer-completed', [LanController::class, 'transferCompleted']);
    Route::get('/notifications', [LanController::class, 'notifications']);
    Route::get('/locations', [LanController::class, 'locations']);
    Route::post('/send', [LanController::class, 'send']);
    Route::post('/retry/{id}', [LanController::class, 'retry']);
});

Route::prefix('app')->middleware('sync.auth')->group(function (): void {
    Route::get('/notifications', [LanController::class, 'appNotifications']);
    Route::post('/notifications/{id}/read', [LanController::class, 'readNotification']);
});
