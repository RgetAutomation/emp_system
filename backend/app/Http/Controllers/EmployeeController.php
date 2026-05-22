<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with(['user', 'department', 'designation'])
            ->where('company_id', $request->user()->company_id)
            ->get();
            
        return response()->json($employees);
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info("Store employee request", [
            'has_profile_photo' => $request->hasFile('profile_photo'),
            'all_files' => array_keys($request->allFiles()),
            'inputs' => $request->except(['password'])
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'employee_id' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'company_id' => $request->user()->company_id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'employee',
            ]);

            // Handle file uploads
            $documents = [];
            $company_id = $request->user()->company_id;
            $filesToUpload = ['profile_photo', 'aadhaar_doc', 'pan_doc', 'other_gov_doc', 'resume', 'education_cert', 'experience_letter', 'signature', 'offer_letter', 'appointment_letter'];

            foreach ($filesToUpload as $fileKey) {
                if ($request->hasFile($fileKey)) {
                    $path = $request->file($fileKey)->store("company_{$company_id}/employees/{$user->id}", 'public');
                    $documents[$fileKey] = $path;
                }
            }

            // Parse JSON strings from FormData if they exist
            $personal_details = $request->filled('personal_details') ? json_decode($request->personal_details, true) : null;
            $bank_details = $request->filled('bank_details') ? json_decode($request->bank_details, true) : null;
            $identity_docs = $request->filled('identity_docs') ? json_decode($request->identity_docs, true) : null;
            $education_experience = $request->filled('education_experience') ? json_decode($request->education_experience, true) : null;

            // Auto generate employee_id if not provided
            $employeeId = $request->employee_id;
            if (empty($employeeId)) {
                $count = Employee::where('company_id', $company_id)->count() + 1;
                do {
                    $employeeId = 'EMP-' . str_pad($count, 4, '0', STR_PAD_LEFT);
                    $exists = Employee::where('company_id', $company_id)->where('employee_id', $employeeId)->exists();
                    if ($exists) {
                        $count++;
                    }
                } while ($exists);
            }

            $employee = Employee::create([
                'user_id' => $user->id,
                'company_id' => $company_id,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'phone' => $request->phone,
                'salary' => $request->salary,
                'join_date' => $request->join_date,
                
                'employee_id' => $employeeId,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'employment_type' => $request->employment_type,
                'status' => $request->status ?? 'active',
                
                'personal_details' => $personal_details,
                'bank_details' => $bank_details,
                'identity_docs' => $identity_docs,
                'education_experience' => $education_experience,
                'documents' => count($documents) > 0 ? $documents : null,
            ]);

            DB::commit();

            return response()->json($employee->load(['user', 'department', 'designation']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::info("Update employee request", [
            'id' => $id,
            'has_profile_photo' => $request->hasFile('profile_photo'),
            'all_files' => array_keys($request->allFiles()),
            'inputs' => $request->except(['password'])
        ]);

        $employee = Employee::where('company_id', $request->user()->company_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$employee->user_id,
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
        ]);

        DB::beginTransaction();

        try {
            $employee->user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            if ($request->filled('password')) {
                $employee->user->update([
                    'password' => Hash::make($request->password)
                ]);
            }

            // Handle file uploads (append or overwrite)
            $documents = $employee->documents ?? [];
            $company_id = $request->user()->company_id;
            $filesToUpload = ['profile_photo', 'aadhaar_doc', 'pan_doc', 'other_gov_doc', 'resume', 'education_cert', 'experience_letter', 'signature', 'offer_letter', 'appointment_letter'];

            foreach ($filesToUpload as $fileKey) {
                if ($request->hasFile($fileKey)) {
                    // Optionally delete old file here before uploading new one
                    $path = $request->file($fileKey)->store("company_{$company_id}/employees/{$employee->user_id}", 'public');
                    $documents[$fileKey] = $path;
                }
            }

            $personal_details = $request->filled('personal_details') ? json_decode($request->personal_details, true) : $employee->personal_details;
            $bank_details = $request->filled('bank_details') ? json_decode($request->bank_details, true) : $employee->bank_details;
            $identity_docs = $request->filled('identity_docs') ? json_decode($request->identity_docs, true) : $employee->identity_docs;
            $education_experience = $request->filled('education_experience') ? json_decode($request->education_experience, true) : $employee->education_experience;

            $employee->update([
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'phone' => $request->phone,
                'salary' => $request->salary,
                'join_date' => $request->join_date,
                
                'employee_id' => $request->employee_id,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'employment_type' => $request->employment_type,
                'status' => $request->status ?? $employee->status,
                
                'personal_details' => $personal_details,
                'bank_details' => $bank_details,
                'identity_docs' => $identity_docs,
                'education_experience' => $education_experience,
                'documents' => count($documents) > 0 ? $documents : null,
            ]);

            DB::commit();

            return response()->json($employee->load(['user', 'department', 'designation']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $employee = Employee::where('company_id', $request->user()->company_id)->findOrFail($id);
        
        // Delete uploaded files
        Storage::disk('public')->deleteDirectory("company_{$employee->company_id}/employees/{$employee->user_id}");
        
        $employee->user->delete();

        return response()->json(['message' => 'Employee deleted successfully']);
    }
}
