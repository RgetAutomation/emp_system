<?php

namespace App\Http\Controllers;

use App\Models\Roster;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RosterController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $startDateStr = $request->query('start_date');
        $endDateStr = $request->query('end_date');

        if (!$startDateStr || !$endDateStr) {
            // Default to current week (Monday to Sunday)
            $now = Carbon::now('Asia/Kolkata');
            $start = $now->startOfWeek()->toDateString();
            $end = $now->endOfWeek()->toDateString();
        } else {
            $start = Carbon::parse($startDateStr)->toDateString();
            $end = Carbon::parse($endDateStr)->toDateString();
        }

        $rosters = Roster::with(['shift'])
            ->where('company_id', $companyId)
            ->whereBetween('date', [$start, $end])
            ->get();

        return response()->json($rosters);
    }

    public function assign(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.employee_id' => 'required|exists:employees,id',
            'assignments.*.date' => 'required|date',
            'assignments.*.shift_id' => 'nullable|exists:shifts,id',
            'assignments.*.status' => 'required|string|in:scheduled,off,absent',
        ]);

        $companyId = $request->user()->company_id;
        $employeeIds = collect($request->assignments)->pluck('employee_id')->unique()->toArray();
        
        // Verify all employees belong to this company in 1 query
        $validEmployees = Employee::where('company_id', $companyId)
            ->whereIn('id', $employeeIds)
            ->pluck('id')
            ->toArray();

        foreach ($employeeIds as $empId) {
            if (!in_array($empId, $validEmployees)) {
                return response()->json(['message' => "Employee ID {$empId} not found or unauthorized."], 403);
            }
        }

        $now = Carbon::now('Asia/Kolkata');
        $upsertData = [];
        $dates = [];

        foreach ($request->assignments as $assign) {
            $formattedDate = Carbon::parse($assign['date'])->toDateString();
            $dates[] = $formattedDate;
            
            $upsertData[] = [
                'company_id' => $companyId,
                'employee_id' => $assign['employee_id'],
                'date' => $formattedDate,
                'shift_id' => $assign['shift_id'],
                'status' => $assign['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $dates = array_unique($dates);

        // Perform mass upsert in a single highly-optimized query
        Roster::upsert(
            $upsertData,
            ['employee_id', 'date'],
            ['shift_id', 'status', 'updated_at']
        );

        // Fetch saved rosters with nested shifts in 1 query
        $savedRosters = Roster::with(['shift'])
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('date', $dates)
            ->get();

        return response()->json([
            'message' => 'Roster assigned successfully.',
            'rosters' => $savedRosters
        ]);
    }

    public function employeeRoster(Request $request)
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 404);
        }

        $startDateStr = $request->query('start_date');
        $endDateStr = $request->query('end_date');

        if (!$startDateStr || !$endDateStr) {
            // Default to current month
            $now = Carbon::now('Asia/Kolkata');
            $start = $now->startOfMonth()->toDateString();
            $end = $now->endOfMonth()->toDateString();
        } else {
            $start = Carbon::parse($startDateStr)->toDateString();
            $end = Carbon::parse($endDateStr)->toDateString();
        }

        $rosters = Roster::with(['shift'])
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->get();

        return response()->json($rosters);
    }
}
