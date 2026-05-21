<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fingerprint_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->nullable();
            $table->float('similarity_score')->nullable();
            $table->enum('status', ['match', 'not_match'])->nullable();
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_logs');
    }
};
