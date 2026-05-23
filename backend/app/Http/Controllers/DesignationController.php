<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $designations = Designation::where('company_id', $request->user()->company_id)
            ->with(['department', 'parent'])
            ->withCount('employees')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($designations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'parent_id' => 'nullable|exists:designations,id',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $designation = Designation::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'parent_id' => $request->parent_id,
            'min_salary' => $request->min_salary,
            'max_salary' => $request->max_salary,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($designation, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'parent_id' => 'nullable|exists:designations,id',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $designation = Designation::where('company_id', $request->user()->company_id)->findOrFail($id);
        $designation->update([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'parent_id' => $request->parent_id,
            'min_salary' => $request->min_salary,
            'max_salary' => $request->max_salary,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($designation);
    }

    public function destroy(Request $request, $id)
    {
        $designation = Designation::where('company_id', $request->user()->company_id)->findOrFail($id);
        $designation->delete();

        return response()->json(['message' => 'Designation deleted successfully']);
    }
}
