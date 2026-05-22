<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $designations = Designation::where('company_id', $request->user()->company_id)->get();
        return response()->json($designations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $designation = Designation::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
        ]);

        return response()->json($designation, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $designation = Designation::where('company_id', $request->user()->company_id)->findOrFail($id);
        $designation->update(['name' => $request->name]);

        return response()->json($designation);
    }

    public function destroy(Request $request, $id)
    {
        $designation = Designation::where('company_id', $request->user()->company_id)->findOrFail($id);
        $designation->delete();

        return response()->json(['message' => 'Designation deleted successfully']);
    }
}
