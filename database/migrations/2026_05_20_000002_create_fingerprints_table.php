<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fingerprints', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->nullable();
            $table->string('finger_type')->nullable();    
            $table->string('device_id')->nullable();
            $table->float('quality_score')->default(0)->nullable();            
            $table->integer('sensor_id')->nullable(); 
            $table->softDeletes();
            $table->timestamps();
            $table->index(['employee_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprints');
    }
};
