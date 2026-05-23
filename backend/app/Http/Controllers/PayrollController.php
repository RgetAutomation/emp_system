<?php

namespace App\Http\Controllers;

use App\Models\PayrollRecord;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PenaltyRule;
use App\Models\SalaryStructure;
use App\Models\TaxDeclaration;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PayrollController extends Controller
{
    /**
     * Preview payroll for a given month for an employee.
     * Computes Gross, Statutory Deductions (PF, ESI, PT), Overtime, and Penalties.
     */
    public function preview(Request $request, $employeeId)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $employee = Employee::where('company_id', $user->company_id)->findOrFail($employeeId);
        $structure = SalaryStructure::where('company_id', $user->company_id)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();

        if (!$structure) {
            return response()->json(['message' => 'No active salary structure found'], 404);
        }

        // 1. Get Attendances
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();
        
        $attendances = Attendance::where('user_id', $employee->user_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $absentCount  = $attendances->where('status', 'absent')->count();
        $lateCount    = $attendances->where('status', 'late')->count();
        $halfDayCount = $attendances->where('status', 'half_day')->count();
        $totalOvertimeMinutes = $attendances->sum('overtime_minutes');

        // 2. Base Gross
        $grossSalary = $structure->calculateGross();
        $basicSalary = $structure->basic_salary;

        // 3. Overtime Pay Calculation
        // Assuming 1.5x basic hourly rate for overtime. Standard 30 days * 8 hours = 240 hours
        $hourlyRate = ($basicSalary / 240); 
        $overtimePay = ($totalOvertimeMinutes / 60) * ($hourlyRate * 1.5);

        // 4. Penalties
        $penaltyRules = PenaltyRule::where('company_id', $user->company_id)->where('active', true)->get();
        $totalPenalty = 0;
        foreach ($penaltyRules as $rule) {
            $occurrences = match ($rule->type) {
                'absent'   => $absentCount,
                'late'     => $lateCount,
                'half_day' => $halfDayCount,
                default    => 0,
            };
            $effectiveOccurrences = max(0, $occurrences - ($rule->applies_after - 1));
            if ($effectiveOccurrences <= 0) continue;

            $penaltyPerOccurrence = $rule->deduction_type === 'percentage'
                ? ($basicSalary * $rule->deduction_value / 100)
                : (float) $rule->deduction_value;
            $totalPenalty += $penaltyPerOccurrence * $effectiveOccurrences;
        }

        $grossAdjusted = max(0, $grossSalary + $overtimePay - $totalPenalty);

        // 5. Statutory Deductions
        $pfDeduction = 0;
        $esiDeduction = 0;
        $ptDeduction = 0;
        
        // PF Calculation (12% of Basic, typically capped at 1800 if basic > 15k)
        $isPfApplicable = $employee->bank_details['pf_applicable'] ?? false;
        if ($isPfApplicable) {
            $pfBasis = min($basicSalary, 15000); // Typical statutory cap
            $pfDeduction = $pfBasis * 0.12;
        }

        // ESI Calculation (0.75% of Gross, applicable if gross <= 21k)
        // Check if ESIC number exists
        $hasEsic = !empty($employee->bank_details['esic_no']);
        if ($hasEsic && $grossSalary <= 21000) {
            $esiDeduction = $grossAdjusted * 0.0075;
        }

        // PT (Professional Tax) Calculation - Simplified Example (Maharashtra slabs)
        // Usually, < 7500 -> 0; 7500-10000 -> 175; > 10000 -> 200 (250 in Feb)
        // We'll use a generic fallback for > 10k -> 200
        if ($grossAdjusted > 10000) {
            $ptDeduction = 200;
        } elseif ($grossAdjusted > 7500) {
            $ptDeduction = 175;
        }

        // TDS Calculation
        $tdsDeduction = 0;
        $financialYear = Carbon::now()->month >= 4 
            ? Carbon::now()->year . '-' . (Carbon::now()->year + 1)
            : (Carbon::now()->year - 1) . '-' . Carbon::now()->year;

        $taxDecl = TaxDeclaration::where('employee_id', $employeeId)
            ->where('financial_year', $financialYear)
            ->where('status', 'approved')
            ->first();

        // Very basic annual projection (12 * monthly gross)
        $annualGross = $grossAdjusted * 12; 
        $taxableIncome = $annualGross;

        if ($taxDecl && $taxDecl->regime === 'old') {
            $standardDeduction = 50000;
            $totalExemptions = $standardDeduction + min($taxDecl->section_80c, 150000) + $taxDecl->section_80d + $taxDecl->home_loan_interest;
            $taxableIncome = max(0, $annualGross - $totalExemptions);
            
            // Basic old regime slabs
            if ($taxableIncome > 1000000) {
                $annualTax = 112500 + (($taxableIncome - 1000000) * 0.30);
            } elseif ($taxableIncome > 500000) {
                $annualTax = 12500 + (($taxableIncome - 500000) * 0.20);
            } elseif ($taxableIncome > 250000) {
                $annualTax = ($taxableIncome - 250000) * 0.05;
            } else {
                $annualTax = 0;
            }
            if ($taxableIncome <= 500000) $annualTax = 0; // 87A rebate
        } else {
            // New Regime (Default)
            $standardDeduction = 50000;
            $taxableIncome = max(0, $annualGross - $standardDeduction);

            if ($taxableIncome > 1500000) {
                $annualTax = 150000 + (($taxableIncome - 1500000) * 0.30);
            } elseif ($taxableIncome > 1200000) {
                $annualTax = 90000 + (($taxableIncome - 1200000) * 0.20);
            } elseif ($taxableIncome > 900000) {
                $annualTax = 45000 + (($taxableIncome - 900000) * 0.15);
            } elseif ($taxableIncome > 600000) {
                $annualTax = 15000 + (($taxableIncome - 600000) * 0.10);
            } elseif ($taxableIncome > 300000) {
                $annualTax = ($taxableIncome - 300000) * 0.05;
            } else {
                $annualTax = 0;
            }
            if ($taxableIncome <= 700000) $annualTax = 0; // 87A rebate
        }

        // Add 4% cess
        $annualTax = $annualTax * 1.04;
        $tdsDeduction = $annualTax / 12;

        $netSalary = $grossAdjusted - $pfDeduction - $esiDeduction - $ptDeduction - $tdsDeduction;

        return response()->json([
            'employee_id' => $employee->id,
            'month' => $month,
            'basic_salary' => round($basicSalary, 2),
            'gross_salary' => round($grossSalary, 2),
            'overtime_pay' => round($overtimePay, 2),
            'total_penalty' => round($totalPenalty, 2),
            'gross_adjusted' => round($grossAdjusted, 2),
            'deductions' => [
                'pf' => round($pfDeduction, 2),
                'esi' => round($esiDeduction, 2),
                'pt' => round($ptDeduction, 2),
                'tds' => round($tdsDeduction, 2),
            ],
            'net_salary' => round($netSalary, 2)
        ]);
    }

    /**
     * Finalize and save payroll for a specific month
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|date_format:Y-m',
            'basic_salary' => 'required|numeric',
            'gross_salary' => 'required|numeric',
            'overtime_pay' => 'required|numeric',
            'pf_deduction' => 'required|numeric',
            'esi_deduction' => 'required|numeric',
            'pt_deduction' => 'required|numeric',
            'tds_deduction' => 'required|numeric',
            'net_salary' => 'required|numeric',
        ]);

        $employee = Employee::where('company_id', $user->company_id)->findOrFail($request->employee_id);

        $record = PayrollRecord::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'company_id' => $user->company_id,
                'month' => $request->month,
            ],
            [
                'basic_salary' => $request->basic_salary,
                'gross_salary' => $request->gross_salary,
                'overtime_pay' => $request->overtime_pay,
                'pf_deduction' => $request->pf_deduction,
                'esi_deduction' => $request->esi_deduction,
                'pt_deduction' => $request->pt_deduction,
                'tds_deduction' => $request->tds_deduction,
                'net_salary' => $request->net_salary,
                'status' => 'paid',
            ]
        );

        return response()->json($record, 201);
    }

    /**
     * Get all finalized payrolls for a month
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        if ($user->role === 'admin') {
            $records = PayrollRecord::where('company_id', $user->company_id)
                ->where('month', $month)
                ->with('employee.user')
                ->get();
            return response()->json($records);
        } else {
            $records = PayrollRecord::where('employee_id', $user->employee->id ?? 0)
                ->orderBy('month', 'desc')
                ->get();
            return response()->json($records);
        }
    }
}
