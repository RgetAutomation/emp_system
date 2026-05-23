<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Models\CompanyHoliday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * GET /api/leaves
     * Admin: all company leaves (with filters)
     * Employee: own leave history
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $query = Leave::where('leaves.company_id', $user->company_id)
                ->with(['user.employee.department', 'user.employee.designation', 'approver'])
                ->orderBy('created_at', 'desc');

            // Optional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->filled('month')) {
                $query->whereMonth('start_date', $request->month);
            }
            if ($request->filled('year')) {
                $query->whereYear('start_date', $request->year);
            }

            return response()->json($query->get());
        } else {
            // Employee sees own leaves
            $leaves = Leave::where('user_id', $user->id)
                ->with(['approver'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($leaves);
        }
    }

    /**
     * POST /api/leaves
     * Employee applies for leave
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'type'       => 'required|string|in:sick,casual,annual,earned,maternity,paternity,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:1000',
        ]);

        // Parse dates first (needed for both holiday check and day count)
        $startDate = Carbon::parse($request->start_date);
        $endDate   = Carbon::parse($request->end_date);

        // Get company's weekly off days (default: saturday, sunday)
        $company    = $user->company;
        $weeklyOff  = $company->settings['weekly_off'] ?? ['saturday', 'sunday'];
        $daysMap    = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];
        $offDayNums = array_map(fn($d) => $daysMap[strtolower($d)] ?? 0, $weeklyOff);

        // Get company holidays within the leave range
        $allHolidays  = CompanyHoliday::where('company_id', $user->company_id)->get();
        $holidayDates = [];
        foreach ($allHolidays as $h) {
            $d = $h->is_recurring
                ? Carbon::createFromFormat('Y-m-d', $startDate->year . '-' . $h->date->format('m-d'))
                : $h->date->copy();
            if ($d->between($startDate, $endDate)) {
                $holidayDates[] = $d->toDateString();
            }
        }

        // Count working days: exclude weekly off days + holidays
        $days    = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $isWeeklyOff = in_array($current->dayOfWeek, $offDayNums);
            $isHoliday   = in_array($current->toDateString(), $holidayDates);
            if (!$isWeeklyOff && !$isHoliday) {
                $days++;
            }
            $current->addDay();
        }
        $days = max(1, $days);

        // Check for overlapping leaves
        $overlap = Leave::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })->first();

        if ($overlap) {
            return response()->json([
                'message' => 'You already have a leave request for overlapping dates.'
            ], 422);
        }

        // Validate against Leave Structure limit
        $employee = $user->employee;
        $leaveStructure = $employee ? $employee->leaveStructure : null;
        if ($leaveStructure && $request->type !== 'unpaid') {
            $allowances = $leaveStructure->allowances ?? [];
            $limit = $allowances[$request->type] ?? 0;

            $used = Leave::where('user_id', $user->id)
                ->whereYear('start_date', $startDate->year)
                ->where('status', 'approved')
                ->where('type', $request->type)
                ->sum('days');

            if ($used + $days > $limit) {
                return response()->json([
                    'message' => 'Insufficient leave balance. You have ' . max(0, $limit - $used) . ' ' . $request->type . ' leaves remaining.'
                ], 422);
            }
        }

        $leave = Leave::create([
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'type'       => $request->type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'days'       => $days,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        return response()->json($leave->load('user'), 201);
    }

    /**
     * PATCH /api/leaves/{id}
     * Admin: approve/reject with optional note
     * Employee: cancel their own pending leave
     */
    public function update(Request $request, $id)
    {
        $user  = $request->user();
        $leave = Leave::where('company_id', $user->company_id)->findOrFail($id);

        if ($user->role === 'admin') {
            $request->validate([
                'status'     => 'required|string|in:approved,rejected',
                'admin_note' => 'nullable|string|max:500',
            ]);

            $leave->update([
                'status'      => $request->status,
                'admin_note'  => $request->admin_note,
                'approved_by' => $user->id,
            ]);
        } else {
            // Employee can only cancel their own pending leave
            if ($leave->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if ($leave->status !== 'pending') {
                return response()->json(['message' => 'Only pending leaves can be cancelled.'], 422);
            }

            $leave->update(['status' => 'cancelled']);
        }

        return response()->json($leave->load(['user.employee.department', 'approver']));
    }

    /**
     * DELETE /api/leaves/{id}  (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $leave = Leave::where('company_id', $user->company_id)->findOrFail($id);
        $leave->delete();

        return response()->json(['message' => 'Leave record deleted.']);
    }

    /**
     * GET /api/leaves/stats
     * Per-employee leave usage summary for current year
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $year = $request->input('year', now()->year);

        if ($user->role === 'admin') {
            // Return stats for all employees
            $employees = User::where('company_id', $user->company_id)
                ->where('role', 'employee')
                ->with('employee.department')
                ->get();

            $stats = $employees->map(function ($emp) use ($year, $user) {
                $leaves = Leave::where('user_id', $emp->id)
                    ->where('company_id', $user->company_id)
                    ->whereYear('start_date', $year)
                    ->where('status', 'approved')
                    ->get();
                $leaveStructure = $emp->employee?->leaveStructure;

                return [
                    'user_id'    => $emp->id,
                    'name'       => $emp->name,
                    'department' => $emp->employee?->department?->name,
                    'leave_structure' => $leaveStructure?->name,
                    'allowances' => $leaveStructure?->allowances,
                    'used'       => [
                        'sick'       => $leaves->where('type', 'sick')->sum('days'),
                        'casual'     => $leaves->where('type', 'casual')->sum('days'),
                        'annual'     => $leaves->where('type', 'annual')->sum('days'),
                        'earned'     => $leaves->where('type', 'earned')->sum('days'),
                        'maternity'  => $leaves->where('type', 'maternity')->sum('days'),
                        'paternity'  => $leaves->where('type', 'paternity')->sum('days'),
                        'unpaid'     => $leaves->where('type', 'unpaid')->sum('days'),
                        'total'      => $leaves->sum('days'),
                    ],
                    'pending_count' => Leave::where('user_id', $emp->id)
                        ->where('company_id', $user->company_id)
                        ->whereYear('start_date', $year)
                        ->where('status', 'pending')->count(),
                ];
            });

            return response()->json($stats);
        } else {
            // Employee's own stats
            $leaves = Leave::where('user_id', $user->id)
                ->whereYear('start_date', $year)
                ->where('status', 'approved')
                ->get();
            $leaveStructure = $user->employee?->leaveStructure;

            return response()->json([
                'year' => $year,
                'leave_structure' => $leaveStructure?->name,
                'allowances' => $leaveStructure?->allowances,
                'used' => [
                    'sick'       => $leaves->where('type', 'sick')->sum('days'),
                    'casual'     => $leaves->where('type', 'casual')->sum('days'),
                    'annual'     => $leaves->where('type', 'annual')->sum('days'),
                    'earned'     => $leaves->where('type', 'earned')->sum('days'),
                    'maternity'  => $leaves->where('type', 'maternity')->sum('days'),
                    'paternity'  => $leaves->where('type', 'paternity')->sum('days'),
                    'unpaid'     => $leaves->where('type', 'unpaid')->sum('days'),
                    'total'      => $leaves->sum('days'),
                ],
                'pending_count' => Leave::where('user_id', $user->id)
                    ->whereYear('start_date', $year)
                    ->where('status', 'pending')->count(),
            ]);
        }
    }
}
