<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function registerCompany(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gst_no' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'trade_license' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $company = Company::create([
                'name' => $request->company_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'gst_no' => $request->gst_no,
            ]);

            // Handle File Uploads
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store("company_{$company->id}/branding", 'public');
                $company->update(['logo' => $logoPath]);
            }

            if ($request->hasFile('trade_license')) {
                $tradeLicensePath = $request->file('trade_license')->store("company_{$company->id}/documents", 'public');
                $company->update(['trade_license' => $tradeLicensePath]);
            }

            $user = User::create([
                'company_id' => $company->id,
                'name' => $request->admin_name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'admin',
            ]);

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user->load('company'),
                'token' => $token,
                'company' => $company,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Registration Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('company'),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('company'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
