<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // identitas utama
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();

            // opsional untuk login manual (kalau mau hybrid login)
            $table->string('password')->nullable();

            // opsional untuk device fingerprint
            $table->string('device_id')->nullable();

            // status user
            $table->boolean('is_active')->default(true);
            $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};