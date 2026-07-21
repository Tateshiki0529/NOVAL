<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\OpenRecordController;
use App\Http\Controllers\ProtocolController;
use App\Http\Controllers\ProtocolRecordController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/protocols', [ProtocolController::class, 'store'])->name('protocols.store');
    Route::post('/protocol-versions/{version}/publish', [ProtocolController::class, 'publish'])->name('protocols.publish');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::post('/logbooks', [LogbookController::class, 'store'])->name('logbooks.store');
    Route::post('/logbooks/{logbook}/records', [ProtocolRecordController::class, 'store'])->name('records.store');
    Route::patch('/logbooks/{logbook}/records/{record}', [ProtocolRecordController::class, 'update'])->name('records.update');
    Route::delete('/logbooks/{logbook}/records/{record}', [ProtocolRecordController::class, 'destroy'])->name('records.destroy');
    Route::post('/open-records', [OpenRecordController::class, 'store'])->name('open-records.store');
    Route::patch('/open-records/{record}', [OpenRecordController::class, 'update'])->name('open-records.update');
    Route::delete('/open-records/{record}', [OpenRecordController::class, 'destroy'])->name('open-records.destroy');
});
