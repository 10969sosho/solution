<?php

use App\Http\Controllers\AdmsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\GolonganController;
use App\Http\Controllers\WorkSettingController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\SeasonalScheduleController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\PotonganTerlambatController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LokasiController;
use Illuminate\Support\Facades\Route;

// Public: login
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public: device (mesin fingerprint) endpoints - tetap terbuka
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

// Autentikasi: seluruh halaman admin
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    Route::resource('employees', EmployeeController::class);
    Route::resource('settings', WorkSettingController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('schedules', EmployeeScheduleController::class);
    Route::resource('seasonal', SeasonalScheduleController::class);

    Route::prefix('attendance')->group(function () {
        Route::get('/latest', [AttendanceController::class, 'latest']);
    });

    Route::get('/permits', [PermitController::class, 'index'])->name('permits.index');
    Route::get('/permits/create', [PermitController::class, 'create'])->name('permits.create');
    Route::post('/permits', [PermitController::class, 'store'])->name('permits.store');
    Route::patch('/permits/{permit}/status', [PermitController::class, 'updateStatus'])->name('permits.status');
    Route::delete('/permits/{permit}', [PermitController::class, 'destroy'])->name('permits.destroy');

    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/mutasi', [LoanController::class, 'mutasi'])->name('loans.mutasi');
    Route::get('/loans/laporan', [LoanController::class, 'laporan'])->name('loans.laporan');
    Route::get('/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::post('/loans/{loan}/payments', [LoanController::class, 'storePayment'])->name('loans.payments');
    Route::get('/loans/{loan}/pay', [LoanController::class, 'paymentCreate'])->name('loans.paymentCreate');
    Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');

    // Master data
    Route::resource('golongans', GolonganController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('jabatans', JabatanController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('lokasis', LokasiController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('/gajis', [GajiController::class, 'index'])->name('gajis.index');
    Route::get('/gajis/{employee}/edit', [GajiController::class, 'edit'])->name('gajis.edit');
    Route::put('/gajis/{employee}', [GajiController::class, 'update'])->name('gajis.update');
    Route::resource('potongan-terlambat', PotonganTerlambatController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Payroll & THR hanya untuk Super Admin / Owner
    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
        Route::post('/payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
        Route::get('/payrolls/thr', [PayrollController::class, 'thr'])->name('payrolls.thr');
        Route::get('/payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show');
        Route::post('/payrolls/{payroll}/paid', [PayrollController::class, 'markPaid'])->name('payrolls.paid');
    });
});