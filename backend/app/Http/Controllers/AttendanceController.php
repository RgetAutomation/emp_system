<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the attendance records.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admins can see all attendance records for their company
            $date = $request->query('date', Carbon::today()->toDateString());
            
            $attendances = Attendance::where('company_id', $user->company_id)
                ->whereDate('date', $date)
                ->with(['user.employee.department', 'user.employee.designation'])
                ->get();
                
            return response()->json($attendances);
        } else {
            // Employees see only their own attendance history
            $attendances = Attendance::where('user_id', $user->id)
                ->orderBy('date', 'desc')
                ->get();
                
            return response()->json($attendances);
        }
    }

    /**
     * Get the logged-in employee's check-in/out status for today.
     */
    public function todayStatus(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        return response()->json($attendance);
    }

    /**
     * Check-in today.
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // Check if already checked in today
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already checked in for today'], 400);
        }

        // Determine status (e.g., late if checked in after 9:15 AM)
        $status = 'present';
        $lateTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 09:15:00');
        if ($now->greaterThan($lateTime)) {
            $status = 'late';
        }

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'date' => $today,
            'check_in' => $now->toTimeString(),
            'status' => $status,
        ]);

        return response()->json($attendance, 201);
    }

    /**
     * Check-out today.
     */
    public function checkOut(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // Find today's attendance record
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'No check-in record found for today'], 400);
        }

        if ($attendance->check_out) {
            return response()->json(['message' => 'Already checked out for today'], 400);
        }

        $attendance->update([
            'check_out' => $now->toTimeString(),
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
            'status' => 'required|string|in:present,absent,half_day,late',
            'check_in' => 'nullable|string',
            'check_out' => 'nullable|string',
        ]);

        $attendance = Attendance::where('company_id', $user->company_id)->findOrFail($id);

        $attendance->update([
            'status' => $request->status,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
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
}
