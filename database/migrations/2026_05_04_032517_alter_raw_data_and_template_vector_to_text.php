<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // raw_data menyimpan base64 string (bukan JSON valid)
        DB::statement('ALTER TABLE fingerprint_samples ALTER COLUMN raw_data TYPE text USING raw_data::text');

        // template_vector menyimpan JSON string dari php (json_encode)
        DB::statement('ALTER TABLE fingerprint_templates ALTER COLUMN template_vector TYPE text USING template_vector::text');
    }

    public function down(): void
    {
        // Hapus data dulu agar tidak error saat cast base64 → json
        DB::table('fingerprint_samples')->truncate();
        DB::table('fingerprint_templates')->truncate();

        DB::statement('ALTER TABLE fingerprint_samples ALTER COLUMN raw_data TYPE json USING NULL::json');
        DB::statement('ALTER TABLE fingerprint_templates ALTER COLUMN template_vector TYPE json USING NULL::json');
    }
};
