<?php

use App\Http\Controllers\AdmsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/dashboard/data', [DashboardController::class, 'data']);

Route::prefix('iclock')->group(function () {
    Route::match(['get', 'post'], '/cdata', [AdmsController::class, 'cdata']);
    Route::get('/getrequest', [AdmsController::class, 'getrequest']);
    Route::match(['get', 'post', 'head'], '', [AdmsController::class, 'ping']);
});

Route::match(['get', 'post'], 'cdata', [AdmsController::class, 'cdata']);
Route::get('getrequest', [AdmsController::class, 'getrequest']);

Route::prefix('api')->middleware(['api.auth', 'throttle:100,1'])->group(function () {
    Route::post('/attendance', [AttendanceController::class, 'store']);
});

Route::prefix('attendance')->group(function () {
    Route::get('/latest', [AttendanceController::class, 'latest']);
});
