<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    public function update(Request $request)
    {
        Log::info("Update company settings request", [
            'has_logo' => $request->hasFile('logo'),
            'has_trade_license' => $request->hasFile('trade_license'),
            'inputs' => $request->all()
        ]);

        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $company = $user->company;
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gst_no' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'trade_license' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'emp_id_prefix' => 'nullable|string|max:20',
            'emp_id_padding' => 'nullable|integer|min:2|max:10',
            'settings' => 'nullable|string',
        ]);

        $company->name = $request->name;
        $company->email = $request->email;
        $company->phone = $request->phone;
        $company->address = $request->address;
        $company->gst_no = $request->gst_no;
        $company->emp_id_prefix = $request->has('emp_id_prefix') ? ($request->emp_id_prefix ?? '') : $company->emp_id_prefix;
        $company->emp_id_padding = $request->has('emp_id_padding') ? ($request->emp_id_padding ?? 4) : $company->emp_id_padding;

        if ($request->has('settings')) {
            $company->settings = json_decode($request->settings, true);
        }

        // Handle logo file upload
        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $logoPath = $request->file('logo')->store("company_{$company->id}/branding", 'public');
            $company->logo = $logoPath;
        }

        // Handle trade license file upload
        if ($request->hasFile('trade_license')) {
            // Delete old trade license if it exists
            if ($company->trade_license) {
                Storage::disk('public')->delete($company->trade_license);
            }
            $tradeLicensePath = $request->file('trade_license')->store("company_{$company->id}/documents", 'public');
            $company->trade_license = $tradeLicensePath;
        }

        $company->save();

        return response()->json($company);
    }
}
