<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\PenaltyRuleController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\LeaveStructureController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TaxDeclarationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\KonnectController;

Route::post('/register-company', [AuthController::class, 'registerCompany']);
Route::post('/login', [AuthController::class, 'login']);

// Storage proxy — serves uploaded files with proper CORS headers
Route::get('/file/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    $mimeType = mime_content_type($fullPath);
    return response()->file($fullPath, [
        'Access-Control-Allow-Origin' => '*',
        'Cache-Control' => 'public, max-age=86400',
        'Content-Type' => $mimeType,
    ]);
})->where('path', '.*');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/company/update', [CompanyController::class, 'update']);

    // Admin Routes
    Route::get('analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('designations', DesignationController::class);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('penalty-rules', PenaltyRuleController::class);
    Route::apiResource('salary-structures', SalaryStructureController::class);
    Route::apiResource('leave-structures', LeaveStructureController::class);
    
    // Payroll
    Route::get('payroll', [PayrollController::class, 'index']);
    Route::get('payroll/preview/{employee_id}', [PayrollController::class, 'preview']);
    Route::post('payroll', [PayrollController::class, 'store']);
    Route::post('/employees/{id}/save-id-card', [EmployeeController::class, 'saveIdCardImage']);

    // Tax Declarations (Form 12BB)
    Route::get('tax-declarations', [TaxDeclarationController::class, 'index']);
    Route::post('tax-declarations', [TaxDeclarationController::class, 'store']);
    Route::patch('tax-declarations/{id}/status', [TaxDeclarationController::class, 'updateStatus']);

    // Expenses
    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::post('expenses', [ExpenseController::class, 'store']);
    Route::patch('expenses/{id}/status', [ExpenseController::class, 'updateStatus']);
    Route::post('expenses/{id}/pay', [ExpenseController::class, 'markAsPaid']);

    // Reports (Challans)
    Route::get('reports/pf-challan', [ReportController::class, 'pfChallan']);
    Route::get('reports/esi-challan', [ReportController::class, 'esiChallan']);

    // Attendance Routes
    Route::get('/attendance/status', [AttendanceController::class, 'todayStatus']);
    Route::get('/attendance/monthly', [AttendanceController::class, 'monthly']);
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

    // Konnect (Employee Social Network)
    Route::get('konnect/feed', [KonnectController::class, 'feed']);
    Route::post('konnect/posts', [KonnectController::class, 'storePost']);
    Route::delete('konnect/posts/{id}', [KonnectController::class, 'deletePost']);
    Route::post('konnect/posts/{id}/comments', [KonnectController::class, 'storeComment']);
    Route::delete('konnect/comments/{id}', [KonnectController::class, 'deleteComment']);
    Route::post('konnect/posts/{id}/like', [KonnectController::class, 'toggleLike']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::apiResource('attendance', AttendanceController::class)->only(['index', 'update', 'destroy']);

    // Shift & Roster Routes
    Route::apiResource('shifts', ShiftController::class);
    Route::get('/rosters', [RosterController::class, 'index']);
    Route::post('/rosters/assign', [RosterController::class, 'assign']);
    Route::get('/employee/roster', [RosterController::class, 'employeeRoster']);

    // Leave Management Routes
    Route::get('/leaves/stats', [LeaveController::class, 'stats']);
    Route::apiResource('leaves', LeaveController::class)->except(['show']);

    // Company Holidays & Weekly Off Routes
    Route::get('/holidays/check', [HolidayController::class, 'check']);
    Route::post('/holidays/weekly-off', [HolidayController::class, 'updateWeeklyOff']);
    Route::apiResource('holidays', HolidayController::class)->except(['show']);

    // Penalty Rules
    Route::apiResource('penalty-rules', PenaltyRuleController::class)->except(['show']);

    // Salary Structures
    Route::get('/salary-structures/calculate/{employeeId}', [SalaryStructureController::class, 'calculate']);
    Route::apiResource('salary-structures', SalaryStructureController::class)->except(['show']);
});
