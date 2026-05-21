<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * POST /api/sync
     *
     * Body JSON dari ESP32:
     * {
     *   "device_id": "ESP32-001",
     *   "employees": [
     *     {"id": 1, "name": "Karyawan_1", "finger_page": 0}
     *   ],
     *   "logs": [
     *     {"employee_id": 1, "employee_name": "Karyawan_1", "score": 180,
     *      "status": "match", "time_ms": 99999}
     *   ]
     * }
     */
    public function sync(Request $request)
    {
        $device_id = $request->input('device_id', 'unknown');
        $employees = $request->input('employees', []);
        $logs      = $request->input('logs', []);

        DB::beginTransaction();

        $employee_count = 0;
        foreach ($employees as $e) {
            Employee::updateOrCreate(
                ['finger_page' => $e['finger_page'], 'device_id' => $device_id],
                ['name' => $e['name'] ?? ('Karyawan_' . $e['finger_page'])]
            );
            $employee_count++;
        }

        $log_count = 0;
        foreach ($logs as $l) {
            $status = in_array($l['status'] ?? '', ['match', 'not_match'])
                ? $l['status']
                : 'not_match';

            AttendanceLog::create([
                'employee_id'   => $l['employee_id'] ?? null,
                'employee_name' => $l['employee_name'] ?? null,
                'score'         => $l['score'] ?? 0,
                'status'        => $status,
                'time_ms'       => $l['time_ms'] ?? 0,
                'device_id'     => $device_id,
            ]);
            $log_count++;
        }

        DB::commit();

        return response()->json([
            'status'    => 'success',
            'synced'    => ['employees' => $employee_count, 'logs' => $log_count],
            'device_id' => $device_id,
        ]);
    }

    /**
     * GET /api/employees-by-device?device_id=ESP32-001
     * Dipakai ESP32 untuk download ulang daftar karyawan jika flashdisk diganti.
     */
    public function employeesByDevice(Request $request)
    {
        $device_id = $request->query('device_id');

        $employees = Employee::when($device_id, fn($q) => $q->where('device_id', $device_id))
            ->orderBy('finger_page')
            ->get(['id', 'name', 'finger_page']);

        return response()->json(['status' => 'success', 'data' => $employees]);
    }
}
