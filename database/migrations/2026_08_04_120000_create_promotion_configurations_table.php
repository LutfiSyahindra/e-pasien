<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.default'))
            ->create('promotion_configurations', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 32)->unique();
                $table->unsignedInteger('default_duration_value')->default(1);
                $table->string('default_duration_unit', 12)->default('day');
                $table->boolean('auto_delete_enabled')->default(true);
                $table->unsignedInteger('delete_grace_value')->default(0);
                $table->string('delete_grace_unit', 12)->default('hour');
                $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('database.default'))
            ->dropIfExists('promotion_configurations');
    }
};
