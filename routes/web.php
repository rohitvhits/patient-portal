<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DocumentController;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'requestOtp'])->middleware('throttle:5,1')->name('login.request-otp');
    Route::get('/login/verify', [AuthController::class, 'showOtp'])->name('login.otp');
    Route::post('/login/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('login.verify-otp');
});

Route::middleware(['auth:patient', 'patient.idle'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');

    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});
