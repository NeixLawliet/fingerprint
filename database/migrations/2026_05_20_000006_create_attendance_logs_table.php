<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->integer('score')->default(0)->nullable();
            $table->enum('status', ['match', 'not_match'])->nullable();
            $table->unsignedBigInteger('time_ms')->nullable();
            $table->string('device_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
