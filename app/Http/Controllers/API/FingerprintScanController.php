<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fingerprints;
use App\Models\FingerprintSamples;
use App\Models\FingerprintTemplates;
use App\Models\FingerprintLogs;
use App\Services\FingerprintProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Single-endpoint fingerprint scan.
 *
 * ESP32 cukup kirim SATU request dengan raw_data.
 * Server mengurus: simpan → ekstrak → match → log → return.
 */
class FingerprintScanController extends Controller
{
    const CACHE_KEY = 'fp_templates_v4';
    const CACHE_TTL = 600; // 10 menit

    /**
     * POST /api/scan
     *
     * Body JSON:
     * {
     *   "employee_id"  : 3,            // null = mode absen (unknown)
     *   "finger_type"  : "right_thumb",
     *   "device_id"    : "ESP32-001",
     *   "quality_score": 87.5,
     *   "raw_data"     : "base64..."
     * }
     */
    public function scan(Request $request)
    {
        $t0 = microtime(true);

        $raw_data      = $request->input('raw_data', '');
        $employee_id   = $request->input('employee_id');
        $finger_type   = $request->input('finger_type', 'right_thumb');
        $device_id     = $request->input('device_id', 'ESP32-001');
        $quality_score = $request->input('quality_score', 0);

        if (empty($raw_data)) {
            return response()->json(['status' => 'error', 'message' => 'raw_data kosong'], 422);
        }

        // ── 1. Simpan fingerprint + sample ───────────────────────────────────
        DB::beginTransaction();
        $fingerprint = Fingerprints::create([
            'employee_id'   => $employee_id,
            'finger_type'   => $finger_type,
            'device_id'     => $device_id,
            'quality_score' => $quality_score,
        ]);
        FingerprintSamples::create([
            'fingerprint_id' => $fingerprint->id,
            'sample_index'   => 0,
            'raw_data'       => $raw_data,
        ]);
        DB::commit();

        $t1 = microtime(true);

        // ── 2. Ekstrak fitur ─────────────────────────────────────────────────
        $vector = FingerprintProcessor::extractFeatures($raw_data);
        if (empty($vector)) {
            return response()->json(['status' => 'error', 'message' => 'Gagal ekstrak fitur'], 422);
        }

        $t2 = microtime(true);

        // ── 3. Simpan template + invalidasi cache ────────────────────────────
        FingerprintTemplates::updateOrCreate(
            ['fingerprint_id' => $fingerprint->id],
            [
                'template_vector'   => json_encode($vector),
                'algorithm_version' => FingerprintProcessor::VERSION,
            ]
        );
        Cache::forget(self::CACHE_KEY);

        $t3 = microtime(true);

        // ── 4. Matching ──────────────────────────────────────────────────────
        $match_result = $this->matchFromCache($vector, $fingerprint->id, $employee_id);

        $t4 = microtime(true);

        // ── 5. Log hasil ─────────────────────────────────────────────────────
        $log_employee_id = $employee_id ?? $match_result['matched_employee_id'];
        FingerprintLogs::create([
            'employee_id'      => $log_employee_id,
            'similarity_score' => $match_result['score'],
            'status'           => $match_result['status'],
            'note'             => $match_result['note'],
        ]);

        $t5 = microtime(true);

        return response()->json([
            'status'         => 'success',
            'fingerprint_id' => $fingerprint->id,
            'match'          => [
                'status'                => $match_result['status'],
                'similarity_score'      => $match_result['score'],
                'matched_employee_id'   => $match_result['matched_employee_id'],
                'matched_employee_name' => $match_result['matched_employee_name'],
                'note'                  => $match_result['note'],
            ],
            '_ms' => [
                'db_save'  => round(($t1 - $t0) * 1000),
                'extract'  => round(($t2 - $t1) * 1000),
                'template' => round(($t3 - $t2) * 1000),
                'match'    => round(($t4 - $t3) * 1000),
                'log'      => round(($t5 - $t4) * 1000),
                'total'    => round(($t5 - $t0) * 1000),
            ],
        ]);
    }

    private function matchFromCache(array $query_vec, int $skip_fp_id, ?int $employee_id): array
    {
        $templates = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->loadAllTemplates();
        });

        $best       = 0.0;
        $best_entry = null;

        foreach ($templates as $tpl) {
            if ($tpl['fingerprint_id'] === $skip_fp_id) continue;

            $score = FingerprintProcessor::cosineSimilarity($query_vec, $tpl['vector']);

            if ($score > $best) {
                $best       = $score;
                $best_entry = $tpl;
                if ($score > 0.97) break;
            }
        }

        $others_count = count(array_filter($templates, fn($t) => $t['fingerprint_id'] !== $skip_fp_id));
        if ($others_count === 0) {
            return [
                'status'                => 'match',
                'score'                 => 1.0,
                'matched_employee_id'   => $employee_id,
                'matched_employee_name' => null,
                'note'                  => 'Enrollment pertama — template baru disimpan',
            ];
        }

        if (FingerprintProcessor::isMatch($best) && $best_entry) {
            return [
                'status'                => 'match',
                'score'                 => $best,
                'matched_employee_id'   => $best_entry['employee_id'],
                'matched_employee_name' => $best_entry['employee_name'],
                'note'                  => 'Cocok: ' . ($best_entry['employee_name'] ?? 'employee_id ' . $best_entry['employee_id']),
            ];
        }

        return [
            'status'                => 'not_match',
            'score'                 => $best,
            'matched_employee_id'   => null,
            'matched_employee_name' => null,
            'note'                  => 'Tidak cocok. Score: ' . $best . ' (threshold: ' . FingerprintProcessor::THRESHOLD . ')',
        ];
    }

    private function loadAllTemplates(): array
    {
        $rows = DB::table('fingerprint_templates as ft')
            ->join('fingerprints as fp', 'fp.id', '=', 'ft.fingerprint_id')
            ->leftJoin('employees as e', 'e.id', '=', 'fp.employee_id')
            ->whereNull('ft.deleted_at')
            ->whereNull('fp.deleted_at')
            ->where('ft.algorithm_version', FingerprintProcessor::VERSION)
            ->select([
                'ft.fingerprint_id',
                'ft.template_vector',
                'fp.employee_id',
                'e.name as employee_name',
            ])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $vec = FingerprintProcessor::decodeVector($row->template_vector);
            if (empty($vec)) continue;

            $result[] = [
                'fingerprint_id' => (int) $row->fingerprint_id,
                'employee_id'    => $row->employee_id,
                'employee_name'  => $row->employee_name,
                'vector'         => $vec,
            ];
        }

        return $result;
    }

    public function clearCache()
    {
        Cache::forget(self::CACHE_KEY);
        return response()->json(['status' => 'success', 'message' => 'Cache template dikosongkan']);
    }
}
