<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

header('Content-Type: application/json');

try {
    $employees = \App\Models\Employee::with('user')->get();
    
    $data = [];
    foreach ($employees as $emp) {
        $docsChecked = [];
        if ($emp->documents) {
            foreach ($emp->documents as $key => $path) {
                $fullPath = storage_path('app/public/' . $path);
                $docsChecked[$key] = [
                    'path' => $path,
                    'exists' => file_exists($fullPath),
                    'full_path' => $fullPath
                ];
            }
        }
        $data[] = [
            'id' => $emp->id,
            'name' => $emp->user->name ?? 'N/A',
            'email' => $emp->user->email ?? 'N/A',
            'employee_id' => $emp->employee_id,
            'documents' => $docsChecked,
            'personal_details' => $emp->personal_details,
            'id_card_image' => $emp->id_card_image,
        ];
    }

    $companies = \App\Models\Company::all();
    $companyData = [];
    foreach ($companies as $comp) {
        $logoFullPath = storage_path('app/public/' . $comp->logo);
        $companyData[] = [
            'id' => $comp->id,
            'name' => $comp->name,
            'logo' => $comp->logo,
            'logo_exists' => file_exists($logoFullPath),
            'logo_full_path' => $logoFullPath
        ];
    }

    echo json_encode([
        'status' => 'success',
        'employees' => $data,
        'companies' => $companyData
    ], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
