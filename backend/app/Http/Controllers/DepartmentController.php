<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::where('company_id', $request->user()->company_id)
            ->with('manager')
            ->withCount('employees')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $department = Department::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'code' => $request->code,
            'manager_id' => $request->manager_id,
            'location' => $request->location,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($department, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $department = Department::where('company_id', $request->user()->company_id)->findOrFail($id);
        $department->update([
            'name' => $request->name,
            'code' => $request->code,
            'manager_id' => $request->manager_id,
            'location' => $request->location,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($department);
    }

    public function destroy(Request $request, $id)
    {
        $department = Department::where('company_id', $request->user()->company_id)->findOrFail($id);
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully']);
    }
}
