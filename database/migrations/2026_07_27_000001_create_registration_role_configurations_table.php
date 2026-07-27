<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.default'))
            ->create('registration_role_configurations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('role_id')->unique()->constrained('roles')->cascadeOnDelete();
                $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('database.default'))
            ->dropIfExists('registration_role_configurations');
    }
};
