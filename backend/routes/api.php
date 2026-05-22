<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeController;

Route::post('/register-company', [AuthController::class, 'registerCompany']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/test-user', function (Request $request) {
    $attempt = Auth::attempt(['email' => 'johndeo123@gmail.com', 'password' => '123456']);
    return response()->json([
        'attempt_success' => $attempt,
        'user' => \App\Models\User::where('email', 'johndeo123@gmail.com')->first()
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin Routes
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('designations', DesignationController::class);
    Route::apiResource('employees', EmployeeController::class);
});
