<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fingerprint_samples', function (Blueprint $table) {
            $table->id();
            $table->integer('fingerprint_id')->nullable(); // tanpa FK
            $table->integer('sample_index')->nullable();
            $table->json('raw_data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_samples');
    }
};