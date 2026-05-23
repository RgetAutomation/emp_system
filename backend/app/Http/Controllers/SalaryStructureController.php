<?php

namespace App\Http\Controllers;

use App\Models\SalaryStructure;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PenaltyRule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalaryStructureController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $structures = SalaryStructure::where('company_id', $user->company_id)
            ->with(['employee.user', 'employee.department', 'employee.designation'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) {
                /** @var SalaryStructure $s */
                return [
                    ...$s->toArray(),
                    'gross_salary' => $s->calculateGross(),
                ];
            });

        return response()->json($structures);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'name'           => 'required|string|max:255',
            'basic_salary'   => 'required|numeric|min:0',
            'components'     => 'nullable|array',
            'components.*.name'         => 'required|string',
            'components.*.type'         => 'required|in:allowance,deduction',
            'components.*.amount'       => 'required|numeric|min:0',
            'components.*.is_percentage'=> 'nullable|boolean',
            'effective_from' => 'required|date',
            'is_active'      => 'nullable|boolean',
        ]);

        // Deactivate other active structures for the same employee if is_active = true
        if ($data['is_active'] ?? true) {
            SalaryStructure::where('company_id', $user->company_id)
                ->where('employee_id', $data['employee_id'])
                ->update(['is_active' => false]);
        }

        $structure = SalaryStructure::create([
            ...$data,
            'company_id' => $user->company_id,
            'is_active'  => $data['is_active'] ?? true,
        ]);

        $structure->load(['employee.user', 'employee.department', 'employee.designation']);

        return response()->json([
            ...$structure->toArray(),
            'gross_salary' => $structure->calculateGross(),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $structure = SalaryStructure::where('company_id', $user->company_id)->findOrFail($id);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'basic_salary'   => 'sometimes|numeric|min:0',
            'components'     => 'nullable|array',
            'components.*.name'         => 'required_with:components|string',
            'components.*.type'         => 'required_with:components|in:allowance,deduction',
            'components.*.amount'       => 'required_with:components|numeric|min:0',
            'components.*.is_percentage'=> 'nullable|boolean',
            'effective_from' => 'sometimes|date',
            'is_active'      => 'nullable|boolean',
        ]);

        // If being set active, deactivate others for this employee
        if (isset($data['is_active']) && $data['is_active']) {
            SalaryStructure::where('company_id', $user->company_id)
                ->where('employee_id', $structure->employee_id)
                ->where('id', '!=', $id)
                ->update(['is_active' => false]);
        }

        $structure->update($data);
        $structure->load(['employee.user', 'employee.department', 'employee.designation']);

        return response()->json([
            ...$structure->toArray(),
            'gross_salary' => $structure->calculateGross(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $structure = SalaryStructure::where('company_id', $user->company_id)->findOrFail($id);
        $structure->delete();

        return response()->json(['message' => 'Salary structure deleted successfully']);
    }

    /**
     * Calculate net salary for an employee for a given month,
     * applying active penalty rules based on attendance data.
     */
    public function calculate(Request $request, $employeeId)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $month = $request->query('month', Carbon::now()->format('Y-m'));

        // Find employee & salary structure
        $employee = Employee::where('company_id', $user->company_id)->findOrFail($employeeId);
        $structure = SalaryStructure::where('company_id', $user->company_id)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();

        if (!$structure) {
            return response()->json(['message' => 'No active salary structure found for this employee'], 404);
        }

        // Get attendance records for the month
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $employee->user_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $absentCount  = $attendances->where('status', 'absent')->count();
        $lateCount    = $attendances->where('status', 'late')->count();
        $halfDayCount = $attendances->where('status', 'half_day')->count();
        $totalLateMinutes = $attendances->sum('late_minutes');

        // Get active penalty rules
        $penaltyRules = PenaltyRule::where('company_id', $user->company_id)
            ->where('active', true)
            ->get();

        $grossSalary    = $structure->calculateGross();
        $totalPenalty   = 0;
        $penaltyDetails = [];

        foreach ($penaltyRules as $rule) {
            $occurrences = match ($rule->type) {
                'absent'   => $absentCount,
                'late'     => $lateCount,
                'half_day' => $halfDayCount,
                default    => 0,
            };

            // Apply only if occurrences exceed the threshold
            $effectiveOccurrences = max(0, $occurrences - ($rule->applies_after - 1));

            if ($effectiveOccurrences <= 0) continue;

            $penaltyPerOccurrence = $rule->deduction_type === 'percentage'
                ? ($structure->basic_salary * $rule->deduction_value / 100)
                : (float) $rule->deduction_value;

            $penalty = $penaltyPerOccurrence * $effectiveOccurrences;
            $totalPenalty += $penalty;

            $penaltyDetails[] = [
                'rule'         => $rule->name,
                'type'         => $rule->type,
                'occurrences'  => $occurrences,
                'effective'    => $effectiveOccurrences,
                'per_unit'     => round($penaltyPerOccurrence, 2),
                'total'        => round($penalty, 2),
            ];
        }

        $netSalary = max(0, $grossSalary - $totalPenalty);

        return response()->json([
            'employee'          => $employee->load('user'),
            'structure'         => $structure,
            'month'             => $month,
            'attendance_summary' => [
                'present'           => $attendances->whereIn('status', ['present', 'late'])->count(),
                'late'              => $lateCount,
                'absent'            => $absentCount,
                'half_day'          => $halfDayCount,
                'total_late_minutes'=> $totalLateMinutes,
                'working_days'      => $attendances->count(),
            ],
            'gross_salary'    => round($grossSalary, 2),
            'total_penalty'   => round($totalPenalty, 2),
            'net_salary'      => round($netSalary, 2),
            'penalty_details' => $penaltyDetails,
        ]);
    }
}
