<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.default'))
            ->create('patient_guarantor_configurations', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 32)->unique();
                $table->json('allowed_guarantor_codes');
                $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('database.default'))
            ->dropIfExists('patient_guarantor_configurations');
    }
};
