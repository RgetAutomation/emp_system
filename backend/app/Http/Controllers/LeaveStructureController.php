<?php

namespace App\Http\Controllers;

use App\Models\LeaveStructure;
use Illuminate\Http\Request;

class LeaveStructureController extends Controller
{
    /**
     * GET /api/leave-structures
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $structures = LeaveStructure::where('company_id', $user->company_id)->get();
        return response()->json($structures);
    }

    /**
     * POST /api/leave-structures
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'allowances' => 'required|array',
            'allowances.*' => 'integer|min:0',
        ]);

        $structure = LeaveStructure::create([
            'company_id' => $user->company_id,
            'name' => $request->name,
            'description' => $request->description,
            'allowances' => $request->allowances,
        ]);

        return response()->json($structure, 201);
    }

    /**
     * PUT /api/leave-structures/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $structure = LeaveStructure::where('company_id', $user->company_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'allowances' => 'required|array',
            'allowances.*' => 'integer|min:0',
        ]);

        $structure->update([
            'name' => $request->name,
            'description' => $request->description,
            'allowances' => $request->allowances,
        ]);

        return response()->json($structure);
    }

    /**
     * DELETE /api/leave-structures/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $structure = LeaveStructure::where('company_id', $user->company_id)->findOrFail($id);
        
        // Check if assigned to any employees
        if ($structure->employees()->count() > 0) {
            return response()->json(['message' => 'Cannot delete structure because it is assigned to employees.'], 422);
        }

        $structure->delete();
        return response()->json(['message' => 'Leave structure deleted']);
    }
}
