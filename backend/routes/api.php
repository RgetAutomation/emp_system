<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

Route::post('/register-company', [AuthController::class, 'registerCompany']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/employee-login', [AuthController::class, 'employeeLogin']);
Route::post('/pin-login', [AuthController::class, 'pinLogin']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::get('/generate-employee', [AuthController::class, 'generateEmployee']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/set-app-pin', [AuthController::class, 'setAppPin']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
