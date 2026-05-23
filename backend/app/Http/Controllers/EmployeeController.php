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
        $employees = Employee::with(['user', 'department', 'designation', 'leaveStructure'])
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
                $company = \App\Models\Company::find($company_id);
                $prefix = $company && $company->emp_id_prefix !== null ? $company->emp_id_prefix : 'EMP-';
                $padding = $company && $company->emp_id_padding !== null ? $company->emp_id_padding : 4;

                $count = Employee::where('company_id', $company_id)->count() + 1;
                do {
                    $employeeId = $prefix . str_pad($count, $padding, '0', STR_PAD_LEFT);
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
                'email' => $request->email ?? $request->email_address ?? $user->email,
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
                'leave_structure_id' => $request->leave_structure_id,
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
                'email' => $request->email ?? $request->email_address ?? $employee->user->email,
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
                'leave_structure_id' => $request->leave_structure_id,
            ]);

            DB::commit();

            return response()->json($employee->load(['user', 'department', 'designation']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function saveIdCardImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|string'
        ]);

        $employee = Employee::findOrFail($id);

        if ($employee->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $base64_image = $request->input('image');

            if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
                $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
                $type = strtolower($type[1]);

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return response()->json(['message' => 'Invalid image type'], 400);
                }

                $base64_image = str_replace(' ', '+', $base64_image);
                $image_data = base64_decode($base64_image);

                if ($image_data === false) {
                    return response()->json(['message' => 'Base64 decode failed'], 400);
                }

                $fileName = 'id_card_' . time() . '_' . uniqid() . '.' . $type;
                $path = 'employees/id_cards/' . $fileName;

                Storage::disk('public')->put($path, $image_data);

                $documents = $employee->documents ?? [];
                $documents['id_card_image'] = $path;
                $employee->documents = $documents;
                $employee->save();

                return response()->json([
                    'message' => 'ID card image saved successfully',
                    'path' => $path
                ]);
            } else {
                return response()->json(['message' => 'Invalid image data format'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to save image: ' . $e->getMessage()], 500);
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
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120'
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        
        $rows = [];
        if ($extension === 'csv' || $extension === 'txt') {
            $handle = fopen($file->getPathname(), "r");
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $row;
            }
            fclose($handle);
        } else if ($extension === 'xlsx' || $extension === 'xls') {
            $libraryPath = app_path('Libraries/SimpleXLSX.php');
            if (file_exists($libraryPath)) {
                require_once $libraryPath;
            }

            if (class_exists(\Shuchkin\SimpleXLSX::class)) {
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($file->getPathname())) {
                    $rows = $xlsx->rows();
                } else {
                    return response()->json(['message' => \Shuchkin\SimpleXLSX::parseError()], 400);
                }
            } else {
                return response()->json(['message' => 'Excel support is not installed. Please run "composer require shuchkin/simplexlsx" in your backend folder.'], 500);
            }
        } else {
            return response()->json(['message' => 'Unsupported file format'], 400);
        }

        if (empty($rows)) {
            return response()->json(['message' => 'File is empty or invalid format'], 400);
        }

        $header = array_shift($rows);
        if (!$header) {
            return response()->json(['message' => 'Invalid header format'], 400);
        }

        // Normalize header
        $header = array_map(function($col) {
            return strtolower(trim($col));
        }, $header);
        
        $successCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                if (count($row) != count($header)) continue;
                $data = array_combine($header, $row);
                
                if (empty($data['name']) || empty($data['email'])) {
                    continue;
                }

                // Check if user already exists
                if (User::where('email', $data['email'])->exists()) {
                    $errors[] = "Email {$data['email']} already exists.";
                    continue;
                }

                $user = User::create([
                    'company_id' => $request->user()->company_id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password123'),
                    'role' => 'employee',
                ]);

                // Auto generate employee_id
                $company_id = $request->user()->company_id;
                $company = \App\Models\Company::find($company_id);
                $prefix = $company && $company->emp_id_prefix !== null ? $company->emp_id_prefix : 'EMP-';
                $padding = $company && $company->emp_id_padding !== null ? $company->emp_id_padding : 4;

                $count = Employee::where('company_id', $company_id)->count() + 1;
                do {
                    $employeeId = $prefix . str_pad($count, $padding, '0', STR_PAD_LEFT);
                    $exists = Employee::where('company_id', $company_id)->where('employee_id', $employeeId)->exists();
                    if ($exists) {
                        $count++;
                    }
                } while ($exists);

                // Handle Department
                $department_id = null;
                if (!empty($data['department'])) {
                    $dept = \App\Models\Department::firstOrCreate([
                        'company_id' => $company_id,
                        'name' => $data['department']
                    ]);
                    $department_id = $dept->id;
                }

                // Handle Designation
                $designation_id = null;
                if (!empty($data['designation'])) {
                    $desig = \App\Models\Designation::firstOrCreate([
                        'company_id' => $company_id,
                        'name' => $data['designation']
                    ]);
                    $designation_id = $desig->id;
                }

                $personal_details = [
                    'current_address' => $data['current address'] ?? null,
                ];

                $bank_details = [
                    'bank_name' => $data['bank name'] ?? null,
                    'bank_account_no' => $data['account number'] ?? null,
                    'ifsc_code' => $data['ifsc code'] ?? null,
                ];

                $identity_docs = [
                    'pan_no' => $data['pan number'] ?? null,
                    'aadhaar_no' => $data['aadhaar number'] ?? null,
                ];

                Employee::create([
                    'user_id' => $user->id,
                    'company_id' => $company_id,
                    'department_id' => $department_id,
                    'designation_id' => $designation_id,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'],
                    'salary' => isset($data['salary']) && is_numeric($data['salary']) ? $data['salary'] : null,
                    'employee_id' => $employeeId,
                    'gender' => $data['gender'] ?? null,
                    'employment_type' => isset($data['employment type']) && !empty($data['employment type']) ? $data['employment type'] : 'Full-time',
                    'status' => 'active',
                    'join_date' => !empty($data['join date']) ? $data['join date'] : now()->format('Y-m-d'),
                    'dob' => !empty($data['dob']) ? $data['dob'] : null,
                    'personal_details' => $personal_details,
                    'bank_details' => $bank_details,
                    'identity_docs' => $identity_docs,
                ]);

                $successCount++;
            }
            DB::commit();
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            return response()->json([
                'message' => "Successfully imported {$successCount} employees.",
                'errors' => $errors
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            \Log::error('Import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }


}
