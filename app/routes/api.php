<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EmailVerificationController;
use App\Http\Controllers\API\CalendarController;

// Публичные маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Верификация email
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify');

Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

// Защищённые маршруты
Route::middleware('auth:sanctum')->group(function () {
    // Выход — отдельно с name()
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Календарь
    Route::get('calendar', [CalendarController::class, 'index']);
    Route::post('calendar', [CalendarController::class, 'store']);
    Route::get('predictions', [CalendarController::class, 'predictions']);
});

use App\Http\Controllers\API\StatisticsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/statistics', [StatisticsController::class, 'index']);
});
