<?php

use App\Http\Controllers\Api\BinDeviceController;
use App\Http\Middleware\AuthenticateBin;
use Illuminate\Support\Facades\Route;

// Kontrak ESP32 (kompatibel firmware lama): header x-api-key + bin_code.
Route::prefix('bins')
    ->middleware(['throttle:120,1', AuthenticateBin::class])
    ->group(function () {
        Route::get('config', [BinDeviceController::class, 'config']);
        Route::post('status-update', [BinDeviceController::class, 'statusUpdate']);
    });