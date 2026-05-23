<?php

namespace App\Http\Controllers;

use App\Models\TaxDeclaration;
use App\Models\Employee;
use Illuminate\Http\Request;

class TaxDeclarationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $declarations = TaxDeclaration::where('company_id', $user->company_id)
                ->with('employee.user')
                ->get();
            return response()->json($declarations);
        } else {
            $declarations = TaxDeclaration::where('employee_id', $user->employee->id ?? 0)
                ->get();
            return response()->json($declarations);
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $employeeId = $user->role === 'admin' ? $request->employee_id : ($user->employee->id ?? 0);

        $request->validate([
            'financial_year' => 'required|string',
            'regime' => 'required|in:old,new',
            'rent_paid' => 'nullable|numeric|min:0',
            'section_80c' => 'nullable|numeric|min:0',
            'section_80d' => 'nullable|numeric|min:0',
            'home_loan_interest' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|array',
        ]);

        $declaration = TaxDeclaration::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'financial_year' => $request->financial_year,
                'company_id' => $user->company_id,
            ],
            [
                'regime' => $request->regime,
                'rent_paid' => $request->rent_paid ?? 0,
                'section_80c' => $request->section_80c ?? 0,
                'section_80d' => $request->section_80d ?? 0,
                'home_loan_interest' => $request->home_loan_interest ?? 0,
                'other_deductions' => $request->other_deductions ?? [],
                'status' => 'pending' // Resets to pending on update
            ]
        );

        return response()->json($declaration, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string',
        ]);

        $declaration = TaxDeclaration::where('company_id', $user->company_id)->findOrFail($id);
        $declaration->update([
            'status' => $request->status,
            'admin_note' => $request->admin_note,
        ]);

        return response()->json($declaration);
    }
}
