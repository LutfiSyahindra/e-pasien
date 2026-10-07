<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedule_leaves', function (Blueprint $table): void {
            $table->id();
            $table->string('doctor_code', 20);
            $table->string('clinic_code', 5);
            $table->string('day', 10);
            $table->string('start_time', 5);
            $table->timestamps();
            $table->unique(['doctor_code', 'day', 'start_time'], 'doctor_schedule_leaves_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedule_leaves');
    }
};
