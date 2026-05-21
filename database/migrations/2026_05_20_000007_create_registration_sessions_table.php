<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registration_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->enum('status', ['pending', 'scanning', 'complete', 'failed', 'expired'])
                  ->default('pending')->nullable();
            $table->integer('finger_page')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamp('expires_at');
            $table->softDeletes();
            $table->timestamps();
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_sessions');
    }
};
