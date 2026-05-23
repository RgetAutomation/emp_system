<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
    echo json_encode([
        'status' => 'success',
        'message' => 'Connected to database successfully.',
        'tables' => $tables
    ], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not connect to the database.',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
