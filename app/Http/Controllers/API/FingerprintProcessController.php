<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fingerprints;
use App\Models\FingerprintSamples;
use App\Models\FingerprintTemplates;
use App\Models\FingerprintLogs;
use App\Services\FingerprintProcessor;
use Illuminate\Http\Request;
use DB;

class FingerprintProcessController extends Controller
{
    /**
     * POST /api/fingerprints/{id}/process
     *
     * Pipeline:
     *  1. Ambil semua sample milik fingerprint_id
     *  2. Ekstrak feature vector dari tiap sample
     *  3. Rata-ratakan semua vektor (multi-sample enrollment)
     *  4. Simpan / update fingerprint_templates
     *  5. Bandingkan dengan template user LAIN → cari kecocokan
     *  6. Simpan fingerprint_logs
     *  7. Kembalikan hasil ke ESP32
     */
    public function process(Request $request, $id)
    {
        // 1. Validasi fingerprint ada
        $fingerprint = Fingerprints::find($id);
        if (!$fingerprint) {
            return response()->json(['status' => 'error', 'message' => 'Fingerprint tidak ditemukan'], 404);
        }

        // 2. Ambil semua sample
        $samples = FingerprintSamples::where('fingerprint_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('sample_index')
            ->get();

        if ($samples->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada sample untuk fingerprint ini'], 422);
        }

        // 3. Ekstrak fitur dari tiap sample
        $vectors = [];
        foreach ($samples as $sample) {
            if (empty($sample->raw_data)) continue;
            $vec = FingerprintProcessor::extractFeatures($sample->raw_data);
            if (!empty($vec)) {
                $vectors[] = $vec;
            }
        }

        if (empty($vectors)) {
            return response()->json(['status' => 'error', 'message' => 'Gagal mengekstrak fitur dari sample'], 422);
        }

        // 4. Rata-ratakan semua vektor (lebih akurat jika multi-sample)
        $templateVector = FingerprintProcessor::averageVectors($vectors);

        // 5. Simpan / update template
        DB::beginTransaction();

        $template = FingerprintTemplates::where('fingerprint_id', $id)
            ->whereNull('deleted_at')
            ->first();

        if ($template) {
            $template->update([
                'template_vector'   => json_encode($templateVector),
                'algorithm_version' => FingerprintProcessor::VERSION,
            ]);
        } else {
            $template = FingerprintTemplates::create([
                'fingerprint_id'    => $id,
                'template_vector'   => json_encode($templateVector),
                'algorithm_version' => FingerprintProcessor::VERSION,
            ]);
        }

        DB::commit();

        // 6. Bandingkan dengan semua template user LAIN
        $matchResult = $this->matchAgainstAll($templateVector, $id, $fingerprint->user_id);

        // 7. Simpan log — jika mode absen (user_id null), pakai matched_user_id
        $logUserId = $fingerprint->user_id ?? $matchResult['matched_user_id'];
        $log = FingerprintLogs::create([
            'user_id'          => $logUserId,
            'similarity_score' => $matchResult['score'],
            'status'           => $matchResult['status'],
            'note'             => $matchResult['note'],
        ]);

        return response()->json([
            'status'      => 'success',
            'message'     => 'Template berhasil dibuat',
            'template'    => [
                'id'                => $template->id,
                'fingerprint_id'    => $template->fingerprint_id,
                'algorithm_version' => $template->algorithm_version,
                'vector_size'       => count($templateVector),
                'sample_count'      => count($vectors),
            ],
            'match' => [
                'status'           => $matchResult['status'],
                'similarity_score' => $matchResult['score'],
                'matched_user_id'  => $matchResult['matched_user_id'],
                'note'             => $matchResult['note'],
            ],
            'log_id' => $log->id,
        ]);
    }

    /**
     * POST /api/fingerprints/{id}/verify
     *
     * Hanya melakukan matching tanpa menyimpan template baru.
     * Berguna untuk mode verifikasi (cek apakah jari cocok dengan user tertentu).
     */
    public function verify(Request $request, $id)
    {
        $fingerprint = Fingerprints::find($id);
        if (!$fingerprint) {
            return response()->json(['status' => 'error', 'message' => 'Fingerprint tidak ditemukan'], 404);
        }

        // Ambil template yang sudah tersimpan
        $template = FingerprintTemplates::where('fingerprint_id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$template) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Template belum diproses. Jalankan /process terlebih dahulu.',
            ], 422);
        }

        $templateVector = FingerprintProcessor::decodeVector($template->template_vector);
        $matchResult    = $this->matchAgainstAll($templateVector, $id, $fingerprint->user_id);

        // Simpan log
        $log = FingerprintLogs::create([
            'user_id'          => $fingerprint->user_id,
            'similarity_score' => $matchResult['score'],
            'status'           => $matchResult['status'],
            'note'             => $matchResult['note'],
        ]);

        return response()->json([
            'status' => 'success',
            'match'  => [
                'status'           => $matchResult['status'],
                'similarity_score' => $matchResult['score'],
                'matched_user_id'  => $matchResult['matched_user_id'],
                'note'             => $matchResult['note'],
            ],
            'log_id' => $log->id,
        ]);
    }

    // -----------------------------------------------------------------------
    //  PRIVATE HELPER
    // -----------------------------------------------------------------------

    /**
     * Bandingkan templateVector dengan semua template terdaftar.
     * Lewati template milik fingerprint_id yang sama (diri sendiri).
     */
    private function matchAgainstAll(array $templateVector, int $skipFingerprintId, ?int $userId): array
    {
        // Ambil semua template kecuali milik fingerprint ini
        $allTemplates = FingerprintTemplates::whereNull('deleted_at')
            ->where('fingerprint_id', '!=', $skipFingerprintId)
            ->get();

        $bestScore        = 0.0;
        $bestFingerprintId = null;
        $bestUserId       = null;

        foreach ($allTemplates as $tpl) {
            $vec   = FingerprintProcessor::decodeVector($tpl->template_vector);
            if (empty($vec)) continue;

            $score = FingerprintProcessor::cosineSimilarity($templateVector, $vec);

            if ($score > $bestScore) {
                $bestScore         = $score;
                $bestFingerprintId = $tpl->fingerprint_id;

                // Resolve user_id dari fingerprint
                $fp = Fingerprints::find($tpl->fingerprint_id);
                $bestUserId = $fp ? $fp->user_id : null;
            }
        }

        $isMatch = FingerprintProcessor::isMatch($bestScore);

        // Jika tidak ada template lain sama sekali, ini enrollment pertama
        if ($allTemplates->isEmpty()) {
            return [
                'status'          => 'match',
                'score'           => 1.0,
                'matched_user_id' => $userId,
                'note'            => 'Enrollment pertama — template baru disimpan.',
            ];
        }

        if ($isMatch) {
            $user = \App\Models\Users::find($bestUserId);
            $userName = $user ? $user->name : null;
            $userRole = $user ? $user->role : null;
            return [
                'status'            => 'match',
                'score'             => $bestScore,
                'matched_user_id'   => $bestUserId,
                'matched_user_name' => $userName,
                'matched_user_role' => $userRole,
                'note'              => "Cocok: " . ($userName ?? "user_id #{$bestUserId}") . " (score: {$bestScore})",
            ];
        }

        return [
            'status'            => 'not_match',
            'score'             => $bestScore,
            'matched_user_id'   => null,
            'matched_user_name' => null,
            'matched_user_role' => null,
            'note'              => "Tidak cocok. Score tertinggi: {$bestScore} (threshold: " . FingerprintProcessor::THRESHOLD . ')',
        ];
    }
}
