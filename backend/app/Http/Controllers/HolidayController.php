<?php

namespace App\Http\Controllers;

use App\Models\CompanyHoliday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * GET /api/holidays?year=2026
     * Returns all holidays for the company (admin & employee).
     * For recurring holidays, also matches current year if year param matches month+day.
     */
    public function index(Request $request)
    {
        $user    = $request->user();
        $year    = $request->get('year', now()->year);

        $holidays = CompanyHoliday::where('company_id', $user->company_id)
            ->orderBy('date')
            ->get()
            ->filter(function ($h) use ($year) {
                // Non-recurring: only show if the holiday year matches requested year
                if (!$h->is_recurring) {
                    return $h->date->year == $year;
                }
                // Recurring: always show (front-end formats the display year)
                return true;
            })
            ->map(function ($h) use ($year) {
                $data = $h->toArray();
                // For recurring, override displayed date year to requested year
                if ($h->is_recurring) {
                    $data['display_date'] = $year . '-' . $h->date->format('m-d');
                } else {
                    $data['display_date'] = $h->date->format('Y-m-d');
                }
                return $data;
            })
            ->values();

        return response()->json($holidays);
    }

    /**
     * POST /api/holidays  (Admin only)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'         => 'required|string|max:150',
            'date'         => 'required|date',
            'type'         => 'required|string|in:national,company,optional',
            'is_recurring' => 'boolean',
            'description'  => 'nullable|string|max:500',
        ]);

        $holiday = CompanyHoliday::create([
            'company_id'   => $user->company_id,
            'name'         => $request->name,
            'date'         => $request->date,
            'type'         => $request->type,
            'is_recurring' => $request->boolean('is_recurring', false),
            'description'  => $request->description,
        ]);

        return response()->json($holiday, 201);
    }

    /**
     * PUT /api/holidays/{id}  (Admin only)
     */
    public function update(Request $request, $id)
    {
        $user    = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $holiday = CompanyHoliday::where('company_id', $user->company_id)->findOrFail($id);

        $request->validate([
            'name'         => 'required|string|max:150',
            'date'         => 'required|date',
            'type'         => 'required|string|in:national,company,optional',
            'is_recurring' => 'boolean',
            'description'  => 'nullable|string|max:500',
        ]);

        $holiday->update([
            'name'         => $request->name,
            'date'         => $request->date,
            'type'         => $request->type,
            'is_recurring' => $request->boolean('is_recurring', false),
            'description'  => $request->description,
        ]);

        return response()->json($holiday);
    }

    /**
     * DELETE /api/holidays/{id}  (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $holiday = CompanyHoliday::where('company_id', $user->company_id)->findOrFail($id);
        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted.']);
    }

    /**
     * GET /api/holidays/check?start=2026-05-01&end=2026-05-10
     * Returns holiday dates between a range (used by leave day calculator).
     */
    public function check(Request $request)
    {
        $user  = $request->user();
        $start = Carbon::parse($request->start);
        $end   = Carbon::parse($request->end);
        $year  = $start->year;

        $holidays = CompanyHoliday::where('company_id', $user->company_id)->get();

        $holidayDates = [];
        foreach ($holidays as $h) {
            if ($h->is_recurring) {
                // Map recurring holiday to current year
                $d = Carbon::createFromFormat('Y-m-d', $year . '-' . $h->date->format('m-d'));
            } else {
                $d = $h->date->copy();
            }
            if ($d->between($start, $end)) {
                $holidayDates[] = $d->toDateString();
            }
        }

        return response()->json($holidayDates);
    }

    /**
     * POST /api/holidays/weekly-off  (Admin only)
     * Update weekly off days in company settings.
     * Body: { weekly_off: ['saturday', 'sunday'] }
     */
    public function updateWeeklyOff(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'weekly_off'   => 'required|array',
            'weekly_off.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        $company = $user->company;
        $settings = $company->settings ?? [];
        $settings['weekly_off'] = $request->weekly_off;
        $company->settings = $settings;
        $company->save();

        return response()->json(['weekly_off' => $request->weekly_off]);
    }
}
