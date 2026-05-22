<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::where('company_id', $request->user()->company_id)->get();
        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department = Department::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
        ]);

        return response()->json($department, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department = Department::where('company_id', $request->user()->company_id)->findOrFail($id);
        $department->update(['name' => $request->name]);

        return response()->json($department);
    }

    public function destroy(Request $request, $id)
    {
        $department = Department::where('company_id', $request->user()->company_id)->findOrFail($id);
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully']);
    }
}
