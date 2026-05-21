<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\RegistrationSession;
use App\Services\MqttPublisher;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    // POST /api/registration/start — web buat sesi + publish MQTT ke ESP32
    public function start(Request $request)
    {
        RegistrationSession::where('expires_at', '<', now())
            ->whereIn('status', ['pending', 'scanning'])
            ->update(['status' => 'expired']);

        $session = RegistrationSession::create([
            'name'       => $request->input('name', 'Karyawan Baru'),
            'status'     => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);

        MqttPublisher::publish('pringer/registration/new', [
            'session_id' => $session->id,
            'name'       => $session->name,
        ]);

        return response()->json([
            'status'       => 'success',
            'session_id'   => $session->id,
            'employee_name' => $session->name,
        ]);
    }

    // GET /api/registration/status/{id} — web polling
    public function status($id)
    {
        $session = RegistrationSession::findOrFail($id);
        if ($session->status === 'pending' && $session->expires_at->isPast()) {
            $session->update(['status' => 'expired']);
        }
        return response()->json(['status' => $session->status, 'data' => $session]);
    }

    // POST /api/registration/complete/{id} — ESP32 selesai enroll
    public function complete(Request $request, $id)
    {
        $session     = RegistrationSession::findOrFail($id);
        $finger_page = (int) $request->input('finger_page');
        $device_id   = $request->input('device_id', 'ESP32-001');

        Employee::create([
            'name'        => $session->name,
            'finger_page' => $finger_page,
            'device_id'   => $device_id,
        ]);

        $session->update([
            'status'      => 'complete',
            'finger_page' => $finger_page,
            'device_id'   => $device_id,
        ]);

        return response()->json(['status' => 'success']);
    }

    // POST /api/registration/cancel/{id}
    public function cancel($id)
    {
        RegistrationSession::findOrFail($id)->update(['status' => 'expired']);
        return response()->json(['status' => 'success']);
    }

    // POST /api/registration/failed/{id} — ESP32 laporkan gagal
    public function failed($id)
    {
        RegistrationSession::findOrFail($id)->update(['status' => 'failed']);
        return response()->json(['status' => 'success']);
    }

    // GET /api/registration/pending?device_id=ESP32-001 — ESP32 klaim sesi pending
    public function pending(Request $request)
    {
        $session = RegistrationSession::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$session) {
            return response()->json(['data' => null]);
        }

        $session->update([
            'status'    => 'scanning',
            'device_id' => $request->query('device_id', 'ESP32'),
        ]);

        return response()->json([
            'data' => [
                'id'   => $session->id,
                'name' => $session->name,
            ],
        ]);
    }
}
