<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RegistrationSession;
use App\Models\Users;
use App\Services\MqttPublisher;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    /**
     * POST /api/registration/start
     * Web memanggil ini setelah form diisi.
     * → Buat user → buat sesi → kembalikan session_id untuk polling.
     */
    public function start(Request $request)
    {
        DB::beginTransaction();

        $user = Users::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'role'      => $request->role ?? 'user',
            'device_id' => $request->device_id,
            'is_active' => 1,
        ]);

        $session = RegistrationSession::create([
            'user_id'    => $user->id,
            'status'     => 'pending',
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        DB::commit();

        // Publish MQTT — ESP32 langsung terima dan mulai scan
        // Status tetap 'pending' sampai ESP32 selesai dan panggil /complete
        MqttPublisher::publish('pringer/registration/new', [
            'session_id' => $session->id,
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'user_role'  => $user->role,
        ]);

        return response()->json([
            'status'     => 'success',
            'session_id' => $session->id,
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'expires_at' => $session->expires_at->toISOString(),
        ]);
    }

    /**
     * GET /api/registration/status/{id}
     * Web polling setiap 2 detik untuk tahu apakah fingerprint sudah masuk.
     */
    public function status($id)
    {
        $session = RegistrationSession::find($id);

        if (!$session) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($session->isExpired() && $session->status === 'pending') {
            $session->update(['status' => 'expired']);
        }

        return response()->json([
            'status'         => $session->status,
            'fingerprint_id' => $session->fingerprint_id,
            'user_id'        => $session->user_id,
        ]);
    }

    /**
     * GET /api/registration/pending
     * ESP32 polling setiap 3 detik.
     * → Kembalikan sesi pending + info user jika ada.
     * → Tandai sebagai 'scanning' agar tidak diambil dua kali.
     */
    public function pending()
    {
        $session = RegistrationSession::nextPending();

        if (!$session) {
            return response()->json(['has_pending' => false]);
        }

        // Klaim sesi — ESP32 sedang menangani
        $session->update([
            'status'     => 'scanning',
            'claimed_at' => Carbon::now(),
        ]);

        $user = Users::find($session->user_id);

        return response()->json([
            'has_pending' => true,
            'session_id'  => $session->id,
            'user_id'     => $session->user_id,
            'user_name'   => $user?->name ?? 'Unknown',
            'user_role'   => $user?->role ?? 'user',
        ]);
    }

    /**
     * POST /api/registration/complete/{id}
     * ESP32 memanggil ini setelah fingerprint berhasil di-capture & diproses.
     */
    public function complete(Request $request, $id)
    {
        $session = RegistrationSession::find($id);

        // Terima 'pending' dan 'scanning' — MQTT flow tidak mengubah ke 'scanning' dulu
        if (!$session || !in_array($session->status, ['pending', 'scanning'])) {
            return response()->json(['status' => 'error', 'message' => 'Sesi tidak valid atau sudah selesai'], 422);
        }

        $session->update([
            'status'         => 'complete',
            'fingerprint_id' => $request->fingerprint_id,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Registrasi selesai']);
    }

    /**
     * POST /api/registration/cancel/{id}
     * Web membatalkan sesi (user tutup modal).
     */
    public function cancel($id)
    {
        $session = RegistrationSession::find($id);

        if ($session && in_array($session->status, ['pending', 'scanning'])) {
            $session->update(['status' => 'cancelled']);
        }

        return response()->json(['status' => 'success']);
    }
}
