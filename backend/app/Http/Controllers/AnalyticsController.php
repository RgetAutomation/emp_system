<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Department;
use App\Models\PayrollRecord;
use App\Models\Attendance;
use App\Models\Expense;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $companyId = $user->company_id;

        // 1. Headcount & Departments
        $totalEmployees = User::where('company_id', $companyId)->where('role', 'employee')->count();
        $departments = Department::where('company_id', $companyId)
            ->withCount(['employees' => function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            }])
            ->get()
            ->map(function ($dept) {
                return [
                    'name' => $dept->name,
                    'count' => $dept->employees_count
                ];
            });

        // 2. Monthly Payroll Cost Trend (Last 6 Months)
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $payrolls = PayrollRecord::where('company_id', $companyId)
            ->where('status', 'paid')
            ->where('month', '>=', $sixMonthsAgo->format('Y-m'))
            ->select('month', DB::raw('SUM(gross_salary + overtime_pay) as total_cost'))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();
            
        // Fill missing months
        $payrollTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i)->format('Y-m');
            $record = $payrolls->firstWhere('month', $m);
            $payrollTrend[] = [
                'month' => Carbon::parse($m . '-01')->format('M Y'),
                'cost' => $record ? (float) $record->total_cost : 0
            ];
        }

        // 3. Today's Attendance
        $today = Carbon::today()->toDateString();
        $attendances = Attendance::where('company_id', $companyId)
            ->where('date', $today)
            ->get();
            
        $present = $attendances->whereIn('status', ['present', 'late', 'half_day'])->count();
        $onLeave = \App\Models\Leave::where('company_id', $companyId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();
        $absent = max(0, $totalEmployees - $present - $onLeave);

        $attendanceStats = [
            'present' => $present,
            'on_leave' => $onLeave,
            'absent' => $absent
        ];

        // 4. Expense Distribution (Current Year)
        $startOfYear = Carbon::now()->startOfYear();
        $expenses = Expense::where('company_id', $companyId)
            ->where('status', 'paid')
            ->where('date_incurred', '>=', $startOfYear)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        return response()->json([
            'overview' => [
                'total_employees' => $totalEmployees,
                'total_departments' => $departments->count(),
                'present_today' => $present,
                'on_leave' => $onLeave,
            ],
            'department_distribution' => $departments,
            'payroll_trend' => $payrollTrend,
            'expense_distribution' => $expenses,
            'attendance_today' => $attendanceStats
        ]);
    }
}
