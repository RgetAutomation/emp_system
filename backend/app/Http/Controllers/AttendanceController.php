<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Roster;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admins can see all attendance records for their company
            $date = $request->query('date', Carbon::today()->toDateString());
            
            $employees = \App\Models\User::where('company_id', $user->company_id)
                ->where('role', 'employee')
                ->with(['employee.department', 'employee.designation'])
                ->get();
                
            $attendances = Attendance::where('company_id', $user->company_id)
                ->where('date', $date)
                ->get()
                ->keyBy('user_id');
                
            $result = $employees->map(function ($emp) use ($attendances, $date) {
                if ($attendances->has($emp->id)) {
                    $att = $attendances->get($emp->id);
                    $att->user = $emp;
                    return $att;
                } else {
                    return [
                        'id'           => null,
                        'user_id'      => $emp->id,
                        'company_id'   => $emp->company_id,
                        'date'         => $date,
                        'check_in'     => null,
                        'check_out'    => null,
                        'status'       => 'absent',
                        'late_minutes' => null,
                        'notes'        => null,
                        'user'         => $emp
                    ];
                }
            });
            
            return response()->json($result->values());
        } else {
            // Employees see only their own attendance history
            $attendances = Attendance::where('user_id', $user->id)
                ->orderBy('date', 'desc')
                ->get();
                
            return response()->json($attendances);
        }
    }

    /**
     * Monthly attendance summary for admin.
     * GET /attendance/monthly?month=YYYY-MM&employee_id=X
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function monthly(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        /** @var string $month */
        $month      = $request->query('month', Carbon::now()->format('Y-m'));
        $employeeId = $request->query('employee_id');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // Get all employees of the company
        $employees = \App\Models\User::where('company_id', $user->company_id)
            ->where('role', 'employee')
            ->with(['employee.department', 'employee.designation'])
            ->when($employeeId, function ($q) use ($employeeId) {
                $q->whereHas('employee', fn($eq) => $eq->where('id', $employeeId));
            })
            ->get();

        $attendances = Attendance::where('company_id', $user->company_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($employeeId, function ($q) use ($employees) {
                $q->whereIn('user_id', $employees->pluck('id'));
            })
            ->get()
            ->groupBy('user_id');

        $result = $employees->map(function ($emp) use ($attendances) {
            /** @var \Illuminate\Support\Collection $records */
            $records = $attendances->get($emp->id, collect());
            return [
                'user'               => $emp,
                'present'            => $records->whereIn('status', ['present'])->count(),
                'late'               => $records->where('status', 'late')->count(),
                'absent'             => $records->where('status', 'absent')->count(),
                'half_day'           => $records->where('status', 'half_day')->count(),
                'total_late_minutes' => (int) $records->sum('late_minutes'),
                'working_days'       => $records->count(),
                'records'            => $records->values(),
            ];
        });

        return response()->json([
            'month'     => $month,
            'employees' => $result->values(),
        ]);
    }

    /**
     * Store a newly created attendance record. (Admin only)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'date'         => 'required|date',
            'status'       => 'required|string|in:present,absent,half_day,late',
            'check_in'     => 'nullable|string',
            'check_out'    => 'nullable|string',
            'late_minutes' => 'nullable|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        $attendance = Attendance::updateOrCreate(
            [
                'user_id'    => $request->user_id, 
                'date'       => $request->date, 
                'company_id' => $user->company_id
            ],
            [
                'status'       => $request->status,
                'check_in'     => $request->check_in,
                'check_out'    => $request->check_out,
                'late_minutes' => $request->late_minutes,
                'notes'        => $request->notes,
            ]
        );
        
        // Load relationships
        $attendance->load(['user.employee.department', 'user.employee.designation']);

        return response()->json($attendance, 201);
    }

    /**
     * Get the logged-in employee's check-in/out status for today.
     */
    public function todayStatus(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        return response()->json($attendance);
    }

    /**
     * Check-in today.
     * Calculates late_minutes based on the employee's shift start time or 9:00 AM default.
     */
    public function checkIn(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();
        $now   = Carbon::now();

        // Check if already checked in today
        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already checked in for today'], 400);
        }

        // Determine shift start time from today's roster, or fall back to 9:00 AM
        $shiftStart = $this->getShiftStart($user->id, $today);

        $lateMinutes = 0;
        $status      = 'present';

        if ($now->greaterThan($shiftStart)) {
            $lateMinutes = (int) $now->diffInMinutes($shiftStart);
            if ($lateMinutes > 0) {
                $status = 'late';
            }
        }

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'company_id'   => $user->company_id,
            'date'         => $today,
            'check_in'     => $now->toTimeString(),
            'status'       => $status,
            'late_minutes' => $lateMinutes > 0 ? $lateMinutes : null,
        ]);

        return response()->json($attendance, 201);
    }

    /**
     * Check-out today.
     */
    public function checkOut(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();
        $now   = Carbon::now();

        // Find today's attendance record
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'No check-in record found for today'], 400);
        }

        if ($attendance->check_out) {
            return response()->json(['message' => 'Already checked out for today'], 400);
        }

        $shiftEnd = $this->getShiftEnd($user->id, $today);
        $overtimeMinutes = 0;

        if ($now->greaterThan($shiftEnd)) {
            $overtimeMinutes = (int) $now->diffInMinutes($shiftEnd);
        }

        $attendance->update([
            'check_out' => $now->toTimeString(),
            'overtime_minutes' => $overtimeMinutes > 0 ? $overtimeMinutes : 0,
        ]);

        return response()->json($attendance);
    }

    /**
     * Update the specified attendance record. (Admin only)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status'       => 'required|string|in:present,absent,half_day,late',
            'check_in'     => 'nullable|string',
            'check_out'    => 'nullable|string',
            'late_minutes' => 'nullable|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        $attendance = Attendance::where('company_id', $user->company_id)->findOrFail($id);

        $attendance->update([
            'status'       => $request->status,
            'check_in'     => $request->check_in,
            'check_out'    => $request->check_out,
            'late_minutes' => $request->late_minutes,
            'notes'        => $request->notes,
        ]);

        return response()->json($attendance);
    }

    /**
     * Remove the specified attendance record. (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attendance = Attendance::where('company_id', $user->company_id)->findOrFail($id);
        $attendance->delete();

        return response()->json(['message' => 'Attendance record deleted successfully']);
    }

    /**
     * Get shift start time for a user on a given date from their Roster assignment.
     * Falls back to 09:00:00 if no roster found.
     */
    private function getShiftStart(int $userId, string $date): Carbon
    {
        $dayOfWeek = Carbon::parse($date)->format('l'); // e.g., "Monday"

        $roster = Roster::where('user_id', $userId)
            ->where('day', $dayOfWeek)
            ->with('shift')
            ->first();

        if ($roster && $roster->shift && $roster->shift->start_time) {
            return Carbon::parse($date . ' ' . $roster->shift->start_time);
        }

        // Default shift start: 9:00 AM
        return Carbon::parse($date . ' 09:00:00');
    }

    /**
     * Get shift end time for a user on a given date from their Roster assignment.
     * Falls back to 17:00:00 (5:00 PM) if no roster found.
     */
    private function getShiftEnd(int $userId, string $date): Carbon
    {
        $dayOfWeek = Carbon::parse($date)->format('l');

        $roster = Roster::where('user_id', $userId)
            ->where('day', $dayOfWeek)
            ->with('shift')
            ->first();

        if ($roster && $roster->shift && $roster->shift->end_time) {
            return Carbon::parse($date . ' ' . $roster->shift->end_time);
        }

        // Default shift end: 5:00 PM
        return Carbon::parse($date . ' 17:00:00');
    }
}
