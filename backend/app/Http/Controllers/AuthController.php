<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


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
            'user' => $user->load(['company', 'employee.department', 'employee.designation']),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function employeeLogin(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'password' => 'required|string',
            'phone' => 'required|string',
            'device_id' => 'required|string',
        ]);

        $employeeId = trim($request->employee_id);
        $phone = trim($request->phone);
        $deviceId = trim($request->device_id);

        // Find the employee by employee_id and phone
        $employee = \App\Models\Employee::where('employee_id', $employeeId)
            ->where('phone', $phone)
            ->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Invalid Employee ID or Phone Number.'
            ], 401);
        }

        $user = User::find($employee->user_id);
        $password = trim($request->password);

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password.'
            ], 401);
        }

        // Device Binding Logic
        if (empty($employee->device_id)) {
            // First time login - bind this device
            $employee->device_id = $deviceId;
            $employee->save();
        } elseif ($employee->device_id !== $deviceId) {
            // Attempting to login from a different device
            return response()->json([
                'message' => 'This account is already registered to another device. Please contact your administrator to reset your device.'
            ], 403);
        }

        $token = $user->createToken('employee-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_id' => $employee->employee_id,
                'device_id' => $employee->device_id,
                'has_pin' => !empty($employee->app_pin),
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function setAppPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|digits:4',
        ]);

        $user = $request->user();
        if ($user->role !== 'employee') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = \App\Models\Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->app_pin = Hash::make($request->pin);
        $employee->save();

        return response()->json([
            'message' => 'App PIN set successfully'
        ]);
    }

    public function pinLogin(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'device_id' => 'required|string',
            'pin' => 'required|string|digits:4',
        ]);

        $employee = \App\Models\Employee::where('employee_id', $request->employee_id)
            ->where('device_id', $request->device_id)
            ->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Invalid device or employee. Please login with your full credentials.'
            ], 401);
        }

        if (empty($employee->app_pin) || !Hash::check($request->pin, $employee->app_pin)) {
            return response()->json([
                'message' => 'Invalid PIN.'
            ], 401);
        }

        $user = User::find($employee->user_id);
        $token = $user->createToken('employee-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_id' => $employee->employee_id,
                'device_id' => $employee->device_id,
                'has_pin' => true,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'device_id' => 'required|string',
        ]);

        $email = strtolower(trim($request->email));

        $key = 'send-otp:' . $email;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 4)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);
            return response()->json([
                'message' => 'Too many attempts. Please try again after ' . $hours . ' hours.'
            ], 429);
        }

        $employee = \App\Models\Employee::where('email', $email)->first();
        if (!$employee) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $user = User::find($employee->user_id);
        if (!$user || $user->role !== 'employee') {
            return response()->json(['message' => 'Invalid account type.'], 403);
        }

        if (empty($employee->device_id) || $employee->device_id !== $request->device_id) {
            return response()->json([
                'message' => 'Unrecognized device. Please log in with your Employee ID and password.'
            ], 403);
        }

        $otp = sprintf('%06d', mt_rand(0, 999999));

        $user->otp_code = Hash::make($otp);
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        Mail::to($user->email)->send(new LoginOtpMail($otp));

        \Illuminate\Support\Facades\RateLimiter::hit($key, 3 * 60 * 60); // 3 hours decay
        $attemptsLeft = \Illuminate\Support\Facades\RateLimiter::retriesLeft($key, 4);

        return response()->json([
            'message' => 'OTP sent to your email! (Attempts left: ' . $attemptsLeft . ')'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|digits:6',
            'device_id' => 'required|string',
        ]);

        $email = strtolower(trim($request->email));

        $employee = \App\Models\Employee::where('email', $email)->first();
        if (!$employee) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $user = User::find($employee->user_id);
        if (!$user || $user->role !== 'employee') {
            return response()->json(['message' => 'Invalid account type.'], 403);
        }

        if ($employee->device_id !== $request->device_id) {
            return response()->json(['message' => 'Unrecognized device.'], 403);
        }

        if (empty($user->otp_code) || $user->otp_expires_at < now() || !Hash::check($request->otp, $user->otp_code)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 401);
        }

        // Clear OTP
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        \Illuminate\Support\Facades\RateLimiter::clear('send-otp:' . $email);

        $token = $user->createToken('employee-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_id' => $employee->employee_id,
                'device_id' => $employee->device_id,
                'has_pin' => !empty($employee->app_pin),
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load(['company', 'employee.department', 'employee.designation']));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function generateEmployee()
    {
        $company = Company::firstOrCreate(
            ['email' => 'test@company.com'],
            ['name' => 'Test Company', 'status' => 'active']
        );

        $user = User::firstOrCreate(
            ['email' => 'employee905@test.com'],
            [
                'company_id' => $company->id,
                'name' => 'Test Employee',
                'password' => Hash::make('password123'),
                'role' => 'employee'
            ]
        );

        $employee = \App\Models\Employee::firstOrCreate(
            ['phone' => '9051650478'],
            [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'employee_id' => 'EMP-12345',
            ]
        );

        return response()->json([
            'message' => 'Test Employee Created Successfully!',
            'employee_id' => 'EMP-12345',
            'phone' => '9051650478',
            'password' => 'password123'
        ]);
    }
}
