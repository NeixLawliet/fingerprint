<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registration_sessions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            // pending → scanning → complete | failed | cancelled
            $table->string('status')->default('pending')->index();
            $table->integer('fingerprint_id')->nullable();
            $table->timestamp('expires_at');   // 5 menit dari created_at
            $table->timestamp('claimed_at')->nullable(); // kapan ESP32 ambil
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_sessions');
    }
};
