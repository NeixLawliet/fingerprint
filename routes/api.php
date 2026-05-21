<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\RegistrationController;
use App\Http\Controllers\API\SyncController;
use Illuminate\Support\Facades\Route;

// Registrasi — web buat sesi, ESP32 polling lalu report complete
Route::post('registration/start',         [RegistrationController::class, 'start']);
Route::get('registration/status/{id}',    [RegistrationController::class, 'status']);
Route::post('registration/complete/{id}', [RegistrationController::class, 'complete']);
Route::post('registration/cancel/{id}',   [RegistrationController::class, 'cancel']);
Route::post('registration/failed/{id}',   [RegistrationController::class, 'failed']);

// ESP32 polling: klaim sesi daftar pending untuk device ini
Route::get('registration/pending', [RegistrationController::class, 'pending']);

// Sync dari ESP32 (offline → online)
Route::post('sync',               [SyncController::class, 'sync']);
Route::get('employees-by-device', [SyncController::class, 'employeesByDevice']);

// CRUD employees (web admin)
Route::controller(EmployeeController::class)->group(function () {
    Route::get('employees/{id?}',       'get');
    Route::post('employees',            'post');
    Route::put('employees/{id}',        'put');
    Route::patch('employees/{id}',      'patch');
    Route::delete('employees/{id}',     'delete');
    Route::post('employees_datatables', 'datatables');
});

// CRUD attendance logs
Route::controller(AttendanceController::class)->group(function () {
    Route::get('attendance/{id?}',          'get');
    Route::post('attendance',               'post');
    Route::put('attendance/{id}',           'put');
    Route::patch('attendance/{id}',         'patch');
    Route::delete('attendance/{id}',        'delete');
    Route::post('attendance_datatables',    'datatables');
});

// Attendance logs untuk dashboard (backward-compatible, format {data: [...]})
Route::get('attendance-logs', function (\Illuminate\Http\Request $req) {
    $q = \App\Models\AttendanceLog::orderByDesc('id');
    if ($req->query('limit')) $q->limit((int) $req->query('limit'));
    return response()->json(['data' => $q->get()]);
});

// Attendance stats untuk dashboard
Route::get('attendance-stats', function () {
    $today = \App\Models\AttendanceLog::whereDate('created_at', today());
    return response()->json([
        'today_match' => (clone $today)->where('status', 'match')->count(),
        'total'       => \App\Models\AttendanceLog::count(),
    ]);
});
