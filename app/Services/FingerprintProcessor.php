<?php

namespace App\Services;

/**
 * Fingerprint Feature Extraction v4 — Chirality-Aware HOG (Performance Edition)
 *
 * Optimasi performa dari v4-draft:
 *  1. Flat array untuk Gx/Gy → satu hash lookup vs dua (2-3x lebih cepat)
 *  2. Pre-compute row offset → kurangi perkalian dalam loop
 *  3. Cosine similarity = pure dot product (vektor sudah unit-length → skip norm)
 *  4. array_map + array_sum untuk dot product → eksekusi di level C, bukan PHP loop
 *  5. Skip isset() check → flat array sudah pasti ada (array_fill)
 *
 * Fix chirality dari v3:
 *  asymX/asymY menangkap arah gradien → jempol kiri vs kanan berbeda tanda
 *
 * Fitur: 144 blok × 20 = 2.880 float
 */
class FingerprintProcessor
{
    const WIDTH         = 256;
    const HEIGHT        = 144;
    const BLOCK_SIZE    = 16;
    const HIST_BINS     = 16;
    const THRESHOLD     = 0.80;
    const VERSION       = 'v4';
    const BG_THRESH     = 10.0;
    const CLIP_VAL      = 0.2;
    const CHIRAL_WEIGHT = 2.5;

    // -----------------------------------------------------------------------
    //  ENTRY POINT — input: base64 dari 4-bit packed image (2 piksel/byte)
    //  R503S native: 256×144 piksel @ 4bpp = 18.432 bytes → base64 ~24.576 chars
    // -----------------------------------------------------------------------
    public static function extractFeatures(string $base64_data): array
    {
        $bytes = base64_decode($base64_data, true);
        if ($bytes === false || strlen($bytes) < 500) return [];

        // Unpack 4-bit nibbles → 8-bit pixels (scale 0–15 × 17 = 0–255)
        $raw    = array_values(unpack('C*', $bytes));
        $pixels = [];
        foreach ($raw as $byte) {
            $pixels[] = (($byte >> 4) & 0x0F) * 17;
            $pixels[] = ($byte & 0x0F) * 17;
        }

        $len    = count($pixels);
        $width  = self::WIDTH;
        $height = intdiv($len, $width);
        if ($height < self::BLOCK_SIZE) return [];

        $pixels = self::histEqualize($pixels, $len);

        [$gx, $gy] = self::computeSobelFlat($pixels, $width, $height);

        return self::blockFeatures($gx, $gy, $width, $height);
    }

    // -----------------------------------------------------------------------
    //  HISTOGRAM EQUALIZATION
    // -----------------------------------------------------------------------
    private static function histEqualize(array $pixels, int $len): array
    {
        $hist = array_fill(0, 256, 0);
        foreach ($pixels as $v) $hist[$v]++;

        $cdf = []; $total = 0;
        for ($i = 0; $i < 256; $i++) { $total += $hist[$i]; $cdf[$i] = $total; }

        $cdf_min = 0;
        for ($i = 0; $i < 256; $i++) { if ($cdf[$i] > 0) { $cdf_min = $cdf[$i]; break; } }

        $denom = $len - $cdf_min;
        if ($denom == 0) return $pixels;

        $lut = [];
        for ($i = 0; $i < 256; $i++) {
            $lut[$i] = (int) round(($cdf[$i] - $cdf_min) / $denom * 255);
        }
        return array_map(fn($v) => $lut[$v], $pixels);
    }

    // -----------------------------------------------------------------------
    //  SOBEL 3×3 — FLAT ARRAY (optimasi utama)
    //  Sebelum: $gx[$y][$x] = double hash lookup (lambat)
    //  Sesudah: $gx[$y*$w+$x] = single hash lookup (cepat)
    // -----------------------------------------------------------------------
    private static function computeSobelFlat(array $pixels, int $width, int $height): array
    {
        $size = $width * $height;
        $gx   = array_fill(0, $size, 0.0);
        $gy   = array_fill(0, $size, 0.0);

        for ($y = 1; $y < $height - 1; $y++) {
            // Pre-compute row offsets — hindari perkalian berulang di inner loop
            $yw   = $y * $width;
            $ym1w = $yw - $width;
            $yp1w = $yw + $width;

            for ($x = 1; $x < $width - 1; $x++) {
                $xm1 = $x - 1; $xp1 = $x + 1;

                $tl = $pixels[$ym1w + $xm1]; $tc = $pixels[$ym1w + $x];
                $tr = $pixels[$ym1w + $xp1]; $ml = $pixels[$yw   + $xm1];
                $mr = $pixels[$yw   + $xp1]; $bl = $pixels[$yp1w + $xm1];
                $bc = $pixels[$yp1w + $x];   $br = $pixels[$yp1w + $xp1];

                $idx      = $yw + $x;
                $gx[$idx] = (-$tl - 2*$ml - $bl + $tr + 2*$mr + $br) * 0.125; // /8
                $gy[$idx] = (-$tl - 2*$tc - $tr + $bl + 2*$bc + $br) * 0.125;
            }
        }

        return [$gx, $gy];
    }

    // -----------------------------------------------------------------------
    //  EKSTRAKSI FITUR PER BLOK
    // -----------------------------------------------------------------------
    private static function blockFeatures(array $gx, array $gy, int $width, int $height): array
    {
        $bs   = self::BLOCK_SIZE;
        $bins = self::HIST_BINS;
        $cols = intdiv($width,  $bs);
        $rows = intdiv($height, $bs);

        $features    = [];
        $feat_per_bg = $bins + 4; // jumlah fitur per background block (zeros)

        for ($br = 0; $br < $rows; $br++) {
            for ($bc = 0; $bc < $cols; $bc++) {

                $hist = array_fill(0, $bins, 0.0);
                $sum_mag = $sum_pos_gx = $sum_neg_gx = $sum_pos_gy = $sum_neg_gy = 0.0;
                $vx = $vy = 0.0;
                $mags = [];

                $y_start = $br * $bs + 1; $y_end = ($br + 1) * $bs - 1;
                $x_start = $bc * $bs + 1; $x_end = ($bc + 1) * $bs - 1;

                for ($y = $y_start; $y < $y_end; $y++) {
                    $yw = $y * $width; // pre-compute row offset
                    for ($x = $x_start; $x < $x_end; $x++) {
                        $idx  = $yw + $x;
                        $g_x  = $gx[$idx];
                        $g_y  = $gy[$idx];
                        $mag  = sqrt($g_x * $g_x + $g_y * $g_y);
                        if ($mag < 0.5) continue;

                        // Orientation histogram [0, π)
                        $phi = atan2($g_y, $g_x);
                        if ($phi < 0) $phi += M_PI;
                        $bin = (int)(($phi / M_PI) * $bins);
                        if ($bin >= $bins) $bin = $bins - 1;
                        $hist[$bin] += $mag;

                        // Coherence
                        $two_phi = 2.0 * $phi;
                        $vx     += $mag * cos($two_phi);
                        $vy     += $mag * sin($two_phi);
                        $sum_mag += $mag;
                        $mags[]   = $mag;

                        // Chirality — signed gradient accumulation
                        if ($g_x > 0) $sum_pos_gx += $g_x;
                        else          $sum_neg_gx -= $g_x; // abs
                        if ($g_y > 0) $sum_pos_gy += $g_y;
                        else          $sum_neg_gy -= $g_y;
                    }
                }

                // Background block → zeros
                if (empty($mags) || $sum_mag / count($mags) < self::BG_THRESH) {
                    for ($i = 0; $i < $feat_per_bg; $i++) $features[] = 0.0;
                    continue;
                }

                // L2-Hys pada histogram
                $hist = self::l2hys($hist);

                // Ridge coherence
                $coherence = $sum_mag > 1e-6
                    ? sqrt($vx * $vx + $vy * $vy) / $sum_mag
                    : 0.0;

                // Energy
                $energy = log1p($sum_mag / count($mags)) / log1p(255.0);

                // Chirality asymmetry
                $asym_x = ($sum_pos_gx + $sum_neg_gx > 1e-9)
                    ? ($sum_pos_gx - $sum_neg_gx) / ($sum_pos_gx + $sum_neg_gx)
                    : 0.0;
                $asym_y = ($sum_pos_gy + $sum_neg_gy > 1e-9)
                    ? ($sum_pos_gy - $sum_neg_gy) / ($sum_pos_gy + $sum_neg_gy)
                    : 0.0;

                foreach ($hist as $h) $features[] = round($h, 6);
                $features[] = round($coherence, 6);
                $features[] = round($energy,    6);
                $features[] = round($asym_x * self::CHIRAL_WEIGHT, 6);
                $features[] = round($asym_y * self::CHIRAL_WEIGHT, 6);
            }
        }

        return self::normalize($features);
    }

    // -----------------------------------------------------------------------
    //  L2-HYS
    // -----------------------------------------------------------------------
    private static function l2hys(array $v): array
    {
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $v)));
        if ($norm < 1e-9) return $v;
        $v = array_map(fn($x) => min($x / $norm, self::CLIP_VAL), $v);
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $v)));
        if ($norm < 1e-9) return $v;
        return array_map(fn($x) => $x / $norm, $v);
    }

    // -----------------------------------------------------------------------
    //  COSINE SIMILARITY — UNIT-LENGTH SHORTCUT
    //  Karena semua vektor sudah di-normalize ke unit length:
    //  cos(a,b) = dot(a,b) / (|a|×|b|) = dot(a,b) / 1 = dot(a,b)
    //  array_map + array_sum berjalan di level C → jauh lebih cepat dari PHP loop
    // -----------------------------------------------------------------------
    public static function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) return 0.0;

        // Pastikan panjang sama (jika beda versi template)
        if (count($a) !== count($b)) {
            $a = array_slice($a, 0, $len);
            $b = array_slice($b, 0, $len);
        }

        // Pure dot product — O(n) di C, bukan PHP interpreter
        return round(
            array_sum(array_map(fn($x, $y) => $x * $y, $a, $b)),
            6
        );
    }

    public static function isMatch(float $score): bool
    {
        return $score >= self::THRESHOLD;
    }

    // -----------------------------------------------------------------------
    //  MULTI-SAMPLE AVERAGING
    // -----------------------------------------------------------------------
    public static function averageVectors(array $vectors): array
    {
        $vectors = array_values(array_filter($vectors, fn($v) => !empty($v)));
        if (empty($vectors)) return [];

        $len = count($vectors[0]);
        $avg = array_fill(0, $len, 0.0);
        foreach ($vectors as $vec) {
            for ($i = 0; $i < $len; $i++) $avg[$i] += ($vec[$i] ?? 0.0);
        }
        $n = count($vectors);
        return self::normalize(array_map(fn($v) => $v / $n, $avg));
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
        if (is_string($raw)) { $d = json_decode($raw, true); return is_array($d) ? $d : []; }
        return [];
    }
}
