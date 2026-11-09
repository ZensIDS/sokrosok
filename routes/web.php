<?php

use App\Http\Controllers\Admin\BinController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BinLiveController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitoringController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->homeUrl())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', EnsureRole::class . ':admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('data', [DashboardController::class, 'data'])->name('dashboard.data');

        Route::get('bins/{bin}/data', BinLiveController::class)->name('bins.data');
        Route::resource('bins', BinController::class);
        Route::post('bins/{bin}/regenerate-key', [BinController::class, 'regenerateKey'])->name('bins.regenerate-key');
        Route::post('bins/{bin}/wifi-reset', [BinController::class, 'requestWifiReset'])->name('bins.wifi-reset');
        Route::delete('bins/{bin}/wifi-reset', [BinController::class, 'cancelWifiReset'])->name('bins.wifi-reset.cancel');

        Route::resource('petugas', UserController::class)->parameters(['petugas' => 'user'])->except(['show']);
        Route::resource('nasabah', UserController::class)->parameters(['nasabah' => 'user'])->except(['show']);
    });

Route::middleware(['auth', EnsureRole::class . ':petugas'])
    ->prefix('petugas')->name('petugas.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('data', [DashboardController::class, 'data'])->name('dashboard.data');
        Route::get('bins/{bin}', [MonitoringController::class, 'show'])->name('bins.show');
        Route::get('bins/{bin}/data', BinLiveController::class)->name('bins.data');
    });

Route::middleware(['auth', EnsureRole::class . ':nasabah'])
    ->prefix('nasabah')->name('nasabah.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('data', [DashboardController::class, 'data'])->name('dashboard.data');
        Route::get('bins/{bin}', [MonitoringController::class, 'show'])->name('bins.show');
        Route::get('bins/{bin}/data', BinLiveController::class)->name('bins.data');
    });