<?php

namespace App\Http\Controllers;

use App\Models\PenaltyRule;
use Illuminate\Http\Request;

class PenaltyRuleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = PenaltyRule::where('company_id', $user->company_id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json($rules);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:late,absent,half_day',
            'deduction_type'  => 'required|in:fixed,percentage',
            'deduction_value' => 'required|numeric|min:0',
            'grace_minutes'   => 'nullable|integer|min:0',
            'applies_after'   => 'nullable|integer|min:1',
            'active'          => 'nullable|boolean',
        ]);

        $rule = PenaltyRule::create([
            ...$data,
            'company_id'    => $user->company_id,
            'grace_minutes' => $data['grace_minutes'] ?? 0,
            'applies_after' => $data['applies_after'] ?? 1,
            'active'        => $data['active'] ?? true,
        ]);

        return response()->json($rule, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rule = PenaltyRule::where('company_id', $user->company_id)->findOrFail($id);

        $data = $request->validate([
            'name'            => 'sometimes|string|max:255',
            'type'            => 'sometimes|in:late,absent,half_day',
            'deduction_type'  => 'sometimes|in:fixed,percentage',
            'deduction_value' => 'sometimes|numeric|min:0',
            'grace_minutes'   => 'nullable|integer|min:0',
            'applies_after'   => 'nullable|integer|min:1',
            'active'          => 'nullable|boolean',
        ]);

        $rule->update($data);

        return response()->json($rule);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rule = PenaltyRule::where('company_id', $user->company_id)->findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Penalty rule deleted successfully']);
    }
}
