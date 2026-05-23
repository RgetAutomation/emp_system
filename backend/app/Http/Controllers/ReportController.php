<?php

namespace App\Http\Controllers;

use App\Models\PayrollRecord;
use App\Models\TaxDeclaration;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Generate PF ECR Text format for a given month
     */
    public function pfChallan(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $month = $request->query('month');
        if (!$month) return response()->json(['message' => 'Month is required'], 400);

        $records = PayrollRecord::where('company_id', $user->company_id)
            ->where('month', $month)
            ->where('status', 'paid')
            ->where('pf_deduction', '>', 0)
            ->with('employee.user')
            ->get();

        // ECR Format: UAN#Member Name#Gross Wages#EPF Wages#EPS Wages#EDLI Wages#EE Share(12%)#EPS Share(8.33%)#ER Share(3.67%)#NCP Days#Refunds
        $csvData = [];
        foreach ($records as $r) {
            $uan = $r->employee->bank_details['uan_no'] ?? 'NO_UAN';
            $name = $r->employee->user->name;
            $gross = round($r->gross_salary);
            $epfWages = round(min($r->basic_salary, 15000));
            $epsWages = $epfWages; // typically same cap
            $eeShare = round($r->pf_deduction);
            $epsShare = round($epsWages * 0.0833);
            $erShare = $eeShare - $epsShare;
            $ncpDays = 0; // Requires attendance exact NCP calc

            $csvData[] = implode('#~#', [
                $uan, $name, $gross, $epfWages, $epsWages, $epfWages, $eeShare, $epsShare, $erShare, $ncpDays, 0
            ]);
        }

        $content = implode("\n", $csvData);
        
        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"PF_ECR_{$month}.txt\"");
    }

    /**
     * Generate ESI Contribution Format
     */
    public function esiChallan(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);

        $month = $request->query('month');
        $records = PayrollRecord::where('company_id', $user->company_id)
            ->where('month', $month)
            ->where('status', 'paid')
            ->where('esi_deduction', '>', 0)
            ->with('employee.user')
            ->get();

        // Format: IP Number, IP Name, No of Days Worked, Total Monthly Wages, Reason Code for Zero wages, Last Working Day
        $csvData = [];
        foreach ($records as $r) {
            $ipNo = $r->employee->bank_details['esic_no'] ?? 'NO_ESIC';
            $name = $r->employee->user->name;
            $daysWorked = 30; // simplify for now
            $wages = round($r->gross_salary);
            
            $csvData[] = implode(',', [
                $ipNo, $name, $daysWorked, $wages, '', ''
            ]);
        }

        $content = "IP Number,IP Name,No of Days Worked,Total Monthly Wages,Reason Code for Zero wages,Last Working Day\n" . implode("\n", $csvData);
        
        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"ESI_{$month}.csv\"");
    }
}
