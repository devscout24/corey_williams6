<?php

use App\Http\Controllers\Sync\TransferSyncController;
use App\Http\Controllers\LanController;
use Illuminate\Support\Facades\Route;

Route::middleware('sync.auth')->group(function (): void {
    Route::get('/sync/ping', [TransferSyncController::class, 'ping']);
    Route::get('/sync/transfer-out/{transferId}', [TransferSyncController::class, 'exportTransferOut']);
    Route::post('/sync/transfer-out', [TransferSyncController::class, 'receiveTransferOut']);
});

Route::prefix('lan')->group(function (): void {
    Route::post('/announce', [LanController::class, 'announce']);
    Route::post('/receive', [LanController::class, 'receive']);
    Route::get('/notifications', [LanController::class, 'notifications']);
    Route::get('/locations', [LanController::class, 'locations']);
    Route::post('/send', [LanController::class, 'send']);
    Route::post('/retry/{id}', [LanController::class, 'retry']);
});
