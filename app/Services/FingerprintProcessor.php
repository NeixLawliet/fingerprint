<?php

namespace App\Services;

/**
 * Fingerprint feature extraction and matching.
 *
 * Raw data format from ESP32 / R307 sensor:
 *   256 × 144 pixels, 1 byte per pixel (grayscale 0–255), base64 encoded.
 *   Total = 36 864 bytes per sample.
 *
 * Template vector:
 *   Image is divided into 16 × 9 blocks of 16 × 16 pixels (= 144 blocks).
 *   Per block → [mean, std_deviation] → 288 floats, L2-normalized.
 */
class FingerprintProcessor
{
    const WIDTH      = 256;
    const HEIGHT     = 144;
    const BLOCK_SIZE = 16;
    const THRESHOLD  = 0.82;   // cosine similarity threshold for "match"
    const VERSION    = 'v1';

    // -----------------------------------------------------------------------
    //  FEATURE EXTRACTION
    // -----------------------------------------------------------------------

    /**
     * Extract a 288-float feature vector from a base64-encoded raw sample.
     *
     * @param  string $base64Data
     * @return float[]  Normalized feature vector, or [] on failure.
     */
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

        $bs   = self::BLOCK_SIZE;
        $cols = intdiv($width,  $bs);   // 16
        $rows = intdiv($height, $bs);   // 9 for 144px, more if taller

        $features = [];

        for ($br = 0; $br < $rows; $br++) {
            for ($bc = 0; $bc < $cols; $bc++) {

                $block = [];
                for ($y = 0; $y < $bs; $y++) {
                    for ($x = 0; $x < $bs; $x++) {
                        $idx = ($br * $bs + $y) * $width + ($bc * $bs + $x);
                        if ($idx < $len) {
                            $block[] = ord($bytes[$idx]) / 255.0;
                        }
                    }
                }

                if (empty($block)) {
                    $features[] = 0.0;
                    $features[] = 0.0;
                    continue;
                }

                $n    = count($block);
                $mean = array_sum($block) / $n;

                $var = 0.0;
                foreach ($block as $v) {
                    $diff  = $v - $mean;
                    $var  += $diff * $diff;
                }
                $std = sqrt($var / $n);

                $features[] = round($mean, 6);
                $features[] = round($std,  6);
            }
        }

        return self::normalize($features);
    }

    /**
     * Average multiple feature vectors into one (for multi-sample enrollment).
     *
     * @param  float[][] $vectors
     * @return float[]
     */
    public static function averageVectors(array $vectors): array
    {
        $vectors = array_values(array_filter($vectors, fn($v) => !empty($v)));

        if (empty($vectors)) {
            return [];
        }

        $len = count($vectors[0]);
        $avg = array_fill(0, $len, 0.0);

        foreach ($vectors as $vec) {
            for ($i = 0; $i < $len; $i++) {
                $avg[$i] += $vec[$i] ?? 0.0;
            }
        }

        $n = count($vectors);
        $avg = array_map(fn($v) => $v / $n, $avg);

        return self::normalize($avg);
    }

    // -----------------------------------------------------------------------
    //  SIMILARITY
    // -----------------------------------------------------------------------

    /**
     * Cosine similarity between two L2-normalized vectors → 0.0–1.0.
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }

        $dot   = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        $denom = sqrt($normA) * sqrt($normB);

        return $denom > 0 ? round($dot / $denom, 6) : 0.0;
    }

    /**
     * Whether a similarity score counts as a match.
     */
    public static function isMatch(float $score): bool
    {
        return $score >= self::THRESHOLD;
    }

    // -----------------------------------------------------------------------
    //  HELPERS
    // -----------------------------------------------------------------------

    /** L2-normalize a vector in-place. */
    public static function normalize(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));

        if ($norm < 1e-9) {
            return $vector;
        }

        return array_map(fn($v) => round($v / $norm, 6), $vector);
    }

    /** Decode a template_vector field (JSON string or already-decoded array). */
    public static function decodeVector($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
