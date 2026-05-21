<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fingerprint_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('fingerprint_id')->nullable();
            $table->longText('template_vector')->nullable();
            $table->string('algorithm_version')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['algorithm_version', 'deleted_at'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_templates');
    }
};
