<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EmailVerificationController;
use App\Http\Controllers\API\CalendarController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\StatisticsController;

// Публичные
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Верификация email
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify')
    ->middleware('signed');

Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
    ->name('verification.send')
    ->middleware(['auth:sanctum', 'throttle:6,1']);

// Защищённые маршруты
Route::middleware('auth:sanctum')->group(function () {

    // Выход
    Route::post('/logout', [AuthController::class, 'logout']);

    // Профиль
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);

    // Статистика
    Route::get('/statistics', [StatisticsController::class, 'index']);

    // Календарь
    Route::get('/calendar', [CalendarController::class, 'index']);
    Route::post('/calendar', [CalendarController::class, 'store']); // POST на /calendar
    Route::get('/calendar/symptoms', [CalendarController::class, 'symptoms']);
});
