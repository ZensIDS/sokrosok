<?php

use App\Http\Controllers\Api\BinDeviceController;
use App\Http\Controllers\Api\V1\BinReadController;
use App\Http\Middleware\AuthenticateBin;
use App\Http\Middleware\AuthenticateReadToken;
use Illuminate\Support\Facades\Route;

// Kontrak ESP32 (kompatibel firmware lama): header x-api-key + bin_code.
Route::prefix('bins')
    ->middleware(['throttle:120,1', AuthenticateBin::class])
    ->group(function () {
        Route::get('config', [BinDeviceController::class, 'config']);
        Route::post('status-update', [BinDeviceController::class, 'statusUpdate']);
    });

// API baca-saja untuk app lain (token Bearer terpisah dari API key ESP32).
Route::prefix('v1')
    ->middleware(['throttle:60,1', AuthenticateReadToken::class])
    ->group(function () {
        Route::get('bins', [BinReadController::class, 'index']);
        Route::get('bins/{code}', [BinReadController::class, 'show'])->where('code', '[A-Za-z0-9_-]+');
        Route::get('map', [BinReadController::class, 'map']);
    });