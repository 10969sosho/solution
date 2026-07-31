<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

// API Routes for ADMS - No CSRF protection
Route::middleware(['api.auth', 'throttle:100,1'])->group(function () {
    Route::post('/attendance', [AttendanceController::class, 'store']);
});

// Dashboard Monitoring Routes
Route::prefix('attendance')->group(function () {
    Route::get('/latest', [AttendanceController::class, 'latest']);
});
