<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_access_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_uuid');
            $table->string('platform', 32)->nullable();
            $table->string('browser', 32)->nullable();
            $table->string('last_mode', 12)->default('web');
            $table->boolean('is_pwa_installed')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('last_web_seen_at')->nullable();
            $table->timestamp('last_pwa_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_uuid'], 'user_access_devices_user_device_unique');
            $table->index(['is_pwa_installed', 'last_seen_at'], 'user_access_devices_install_seen_index');
            $table->index(['last_mode', 'last_seen_at'], 'user_access_devices_mode_seen_index');
        });

        Schema::create('user_access_daily', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_access_device_id')
                ->constrained('user_access_devices')
                ->cascadeOnDelete();
            $table->date('access_date');
            $table->string('mode', 12);
            $table->unsignedInteger('visit_count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(
                ['user_access_device_id', 'access_date', 'mode'],
                'user_access_daily_device_date_mode_unique'
            );
            $table->index(['access_date', 'mode', 'user_id'], 'user_access_daily_date_mode_user_index');
            $table->index(['user_id', 'access_date'], 'user_access_daily_user_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_access_daily');
        Schema::dropIfExists('user_access_devices');
    }
};
