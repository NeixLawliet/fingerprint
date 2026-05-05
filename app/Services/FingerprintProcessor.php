<?php

namespace App\Services;

/**
 * Fingerprint Feature Extraction v3 — Ridge-Oriented HOG
 *
 * Perbaikan dari v2:
 *  1. Orientasi dilipat ke [0,π) — ridge bersifat bidireksional, bukan 0-2π
 *     → menghilangkan ambiguitas 180° yang bikin score jari sama jadi rendah
 *  2. 16 bin orientasi (sebelumnya 8) → resolusi 11.25°/bin vs 45°/bin
 *  3. Ridge coherence per blok — fitur khas sidik jari yang sangat diskriminatif
 *  4. Background segmentation — blok tanpa ridge diabaikan agar tidak menambah noise
 *  5. L2-Hys normalisasi per blok — tahan terhadap variasi tekanan & pencahayaan
 *  6. Threshold turun ke 0.82 — lebih toleran terhadap variasi scan natural
 *
 * Format raw data ESP32:
 *   256 × 144 px, 1 byte/pixel grayscale, base64 encoded (36.864 bytes)
 *
 * Fitur:
 *   144 blok (16×9) × 18 fitur/blok = 2.592 float (vs 1.440 di v2)
 */
class FingerprintProcessor
{
    const WIDTH      = 256;
    const HEIGHT     = 144;
    const BLOCK_SIZE = 16;
    const HIST_BINS  = 16;      // orientasi [0,π) → 11.25° per bin
    const THRESHOLD  = 0.80;    // diturunkan dari 0.88 — toleransi variasi scan natural
    const VERSION    = 'v3';
    const BG_THRESH  = 10.0;   // mean gradient < ini = background (abaikan blok)
    const CLIP_VAL   = 0.2;    // L2-Hys clipping

    // -----------------------------------------------------------------------
    //  ENTRY POINT
    // -----------------------------------------------------------------------
    public static function extractFeatures(string $base64Data): array
    {
        $bytes = base64_decode($base64Data, true);
        if ($bytes === false || strlen($bytes) < 1000) {
            return [];
        }

        $len    = strlen($bytes);
        $width  = self::WIDTH;
        $height = intdiv($len, $width);
        if ($height < self::BLOCK_SIZE) {
            return [];
        }

        // 1. Ubah ke array pixel
        $pixels = [];
        for ($i = 0; $i < $len; $i++) {
            $pixels[$i] = ord($bytes[$i]);
        }

        // 2. Histogram equalization — tingkatkan kontras ridge
        $pixels = self::histEqualize($pixels, $len);

        // 3. Hitung Sobel gradient seluruh gambar
        [$Gx, $Gy] = self::computeSobel($pixels, $width, $height);

        // 4. Ekstrak fitur per blok
        return self::blockFeatures($Gx, $Gy, $width, $height);
    }

    // -----------------------------------------------------------------------
    //  HISTOGRAM EQUALIZATION
    // -----------------------------------------------------------------------
    private static function histEqualize(array $pixels, int $len): array
    {
        $hist = array_fill(0, 256, 0);
        foreach ($pixels as $v) $hist[$v]++;

        $cdf = []; $total = 0;
        for ($i = 0; $i < 256; $i++) {
            $total  += $hist[$i];
            $cdf[$i] = $total;
        }

        $cdfMin = 0;
        for ($i = 0; $i < 256; $i++) {
            if ($cdf[$i] > 0) { $cdfMin = $cdf[$i]; break; }
        }

        $denom = $len - $cdfMin;
        if ($denom == 0) return $pixels;

        $lut = [];
        for ($i = 0; $i < 256; $i++) {
            $lut[$i] = (int) round(($cdf[$i] - $cdfMin) / $denom * 255);
        }

        return array_map(fn($v) => $lut[$v], $pixels);
    }

    // -----------------------------------------------------------------------
    //  SOBEL 3×3 — hitung Gx dan Gy seluruh gambar
    // -----------------------------------------------------------------------
    private static function computeSobel(array $pixels, int $width, int $height): array
    {
        $Gx = []; $Gy = [];

        for ($y = 1; $y < $height - 1; $y++) {
            for ($x = 1; $x < $width - 1; $x++) {
                $tl = $pixels[($y-1)*$width+($x-1)];
                $tc = $pixels[($y-1)*$width+$x];
                $tr = $pixels[($y-1)*$width+($x+1)];
                $ml = $pixels[$y*$width+($x-1)];
                $mr = $pixels[$y*$width+($x+1)];
                $bl = $pixels[($y+1)*$width+($x-1)];
                $bc = $pixels[($y+1)*$width+$x];
                $br = $pixels[($y+1)*$width+($x+1)];

                // Bagi 8 agar magnitude dalam rentang yang konsisten
                $Gx[$y][$x] = (-$tl - 2*$ml - $bl + $tr + 2*$mr + $br) / 8.0;
                $Gy[$y][$x] = (-$tl - 2*$tc - $tr + $bl + 2*$bc + $br) / 8.0;
            }
        }

        return [$Gx, $Gy];
    }

    // -----------------------------------------------------------------------
    //  EKSTRAKSI FITUR PER BLOK
    // -----------------------------------------------------------------------
    private static function blockFeatures(array $Gx, array $Gy, int $width, int $height): array
    {
        $bs   = self::BLOCK_SIZE;
        $bins = self::HIST_BINS;
        $cols = intdiv($width, $bs);   // 16
        $rows = intdiv($height, $bs);  // 9

        $features = [];

        for ($br = 0; $br < $rows; $br++) {
            for ($bc = 0; $bc < $cols; $bc++) {

                $hist   = array_fill(0, $bins, 0.0);
                $mags   = [];
                $sumMag = 0.0;
                $Vx = $Vy = 0.0;  // untuk menghitung coherence

                $yStart = $br * $bs + 1;
                $yEnd   = ($br + 1) * $bs - 1;
                $xStart = $bc * $bs + 1;
                $xEnd   = ($bc + 1) * $bs - 1;

                for ($y = $yStart; $y < $yEnd; $y++) {
                    for ($x = $xStart; $x < $xEnd; $x++) {
                        if (!isset($Gx[$y][$x])) continue;

                        $gx  = $Gx[$y][$x];
                        $gy  = $Gy[$y][$x];
                        $mag = sqrt($gx * $gx + $gy * $gy);

                        if ($mag < 0.5) continue;  // skip pixel hampir datar

                        // ── KUNCI PERBAIKAN ──────────────────────────────
                        // Ridge bersifat bidireksional: orientasi θ dan θ+π adalah ridge yang sama
                        // Lipat ke [0, π) agar konsisten antar-scan:
                        //   atan2(Gy, Gx) → (-π, π]
                        //   if phi < 0: phi += π  → (0, π]
                        $phi = atan2($gy, $gx);
                        if ($phi < 0) $phi += M_PI;

                        $bin = (int)(($phi / M_PI) * $bins);
                        if ($bin >= $bins) $bin = $bins - 1;
                        $hist[$bin] += $mag;

                        // Vektor double-angle untuk coherence (tahan terhadap 180° ambiguitas)
                        $twoPhi  = 2.0 * $phi;
                        $Vx     += $mag * cos($twoPhi);
                        $Vy     += $mag * sin($twoPhi);
                        $sumMag += $mag;
                        $mags[]  = $mag;
                    }
                }

                // ── DETEKSI BACKGROUND ───────────────────────────────────
                // Blok tanpa ridge (background) → tidak informative, pakai nol
                $meanMag = count($mags) > 0 ? $sumMag / count($mags) : 0;
                if ($meanMag < self::BG_THRESH) {
                    // Tambahkan nol agar panjang vektor tetap konsisten
                    for ($i = 0; $i < $bins + 2; $i++) $features[] = 0.0;
                    continue;
                }

                // ── L2-HYS NORMALISASI pada histogram ────────────────────
                $hist = self::l2hys($hist);

                // ── RIDGE COHERENCE [0,1] ─────────────────────────────────
                // Mengukur konsistensi arah ridge dalam blok.
                // Blok dengan ridge lurus → coherence tinggi (≈1)
                // Blok dengan pore/noise → coherence rendah (≈0)
                $coherence = $sumMag > 1e-6
                    ? sqrt($Vx * $Vx + $Vy * $Vy) / $sumMag
                    : 0.0;

                // ── GRADIENT ENERGY (log-scale) ──────────────────────────
                // Representasi kuantitas ridge (bukan hanya orientasi)
                $energy = log1p($meanMag) / log1p(255.0);

                // 16 bin + coherence + energy = 18 fitur per blok
                foreach ($hist as $h) {
                    $features[] = round($h, 6);
                }
                $features[] = round($coherence, 6);
                $features[] = round($energy, 6);
            }
        }

        // L2 normalize seluruh vektor fitur → unit vector untuk cosine similarity
        return self::normalize($features);
    }

    // -----------------------------------------------------------------------
    //  L2-HYS NORMALISASI
    //  Langkah: L2 normalize → clip → L2 normalize ulang
    //  Tujuan: mengurangi pengaruh piksel dengan gradient sangat besar (artifact)
    // -----------------------------------------------------------------------
    private static function l2hys(array $v): array
    {
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $v)));
        if ($norm < 1e-9) return $v;

        $v = array_map(fn($x) => $x / $norm, $v);
        $v = array_map(fn($x) => min($x, self::CLIP_VAL), $v);

        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $v)));
        if ($norm < 1e-9) return $v;

        return array_map(fn($x) => $x / $norm, $v);
    }

    // -----------------------------------------------------------------------
    //  MULTI-SAMPLE AVERAGING
    //  Rata-ratakan vektor dari beberapa sample → template lebih robust
    // -----------------------------------------------------------------------
    public static function averageVectors(array $vectors): array
    {
        $vectors = array_values(array_filter($vectors, fn($v) => !empty($v)));
        if (empty($vectors)) return [];

        $len = count($vectors[0]);
        $avg = array_fill(0, $len, 0.0);

        foreach ($vectors as $vec) {
            for ($i = 0; $i < $len; $i++) {
                $avg[$i] += ($vec[$i] ?? 0.0);
            }
        }

        $n = count($vectors);
        return self::normalize(array_map(fn($v) => $v / $n, $avg));
    }

    // -----------------------------------------------------------------------
    //  COSINE SIMILARITY
    // -----------------------------------------------------------------------
    public static function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) return 0.0;

        $dot = $normA = $normB = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        $denom = sqrt($normA) * sqrt($normB);
        return $denom > 1e-9 ? round($dot / $denom, 6) : 0.0;
    }

    public static function isMatch(float $score): bool
    {
        return $score >= self::THRESHOLD;
    }

    // -----------------------------------------------------------------------
    //  HELPERS
    // -----------------------------------------------------------------------
    public static function normalize(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));
        if ($norm < 1e-9) return $vector;
        return array_map(fn($v) => round($v / $norm, 6), $vector);
    }

    public static function decodeVector($raw): array
    {
        if (is_array($raw))  return $raw;
        if (is_string($raw)) {
            $d = json_decode($raw, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }
}
