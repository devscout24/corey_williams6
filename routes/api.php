<?php

use App\Http\Controllers\Sync\TransferSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware('sync.auth')->group(function (): void {
    Route::get('/sync/ping', [TransferSyncController::class, 'ping']);
    Route::get('/sync/transfer-out/{transferId}', [TransferSyncController::class, 'exportTransferOut']);
    Route::post('/sync/transfer-out', [TransferSyncController::class, 'receiveTransferOut']);
});
