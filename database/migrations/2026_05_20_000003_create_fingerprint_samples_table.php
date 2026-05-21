<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fingerprint_samples', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fingerprint_id');
            $table->unsignedSmallInteger('sample_index');
            $table->longText('raw_data');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('fingerprint_id')->references('id')->on('fingerprints')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_samples');
    }
};
