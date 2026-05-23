<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $shifts = Shift::where('company_id', $companyId)->get();

        // If no shifts are set yet, let's create a few beautiful default shift structures
        if ($shifts->isEmpty()) {
            $defaults = [
                [
                    'company_id' => $companyId,
                    'name' => 'Morning Shift',
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'color_code' => '#3b82f6' // Blue
                ],
                [
                    'company_id' => $companyId,
                    'name' => 'Evening Shift',
                    'start_time' => '17:00:00',
                    'end_time' => '01:00:00',
                    'color_code' => '#f97316' // Orange
                ],
                [
                    'company_id' => $companyId,
                    'name' => 'Night Shift',
                    'start_time' => '01:00:00',
                    'end_time' => '09:00:00',
                    'color_code' => '#8b5cf6' // Purple
                ]
            ];

            foreach ($defaults as $def) {
                Shift::create($def);
            }

            $shifts = Shift::where('company_id', $companyId)->get();
        }

        return response()->json($shifts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
            'color_code' => 'required|string|max:7',
        ]);

        $shift = Shift::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'color_code' => $request->color_code,
        ]);

        return response()->json($shift, 201);
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::where('company_id', $request->user()->company_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
            'color_code' => 'required|string|max:7',
        ]);

        $shift->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'color_code' => $request->color_code,
        ]);

        return response()->json($shift);
    }

    public function destroy(Request $request, $id)
    {
        $shift = Shift::where('company_id', $request->user()->company_id)->findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted successfully.']);
    }
}
