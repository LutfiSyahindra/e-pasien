<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.default'))
            ->create('bpjs_api_logs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('request_id')->unique();
                $table->string('service', 30)->index();
                $table->string('endpoint', 500);
                $table->string('method', 10);
                $table->json('request_payload')->nullable();
                $table->json('response_metadata')->nullable();
                $table->unsignedSmallInteger('http_code')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('database.default'))
            ->dropIfExists('bpjs_api_logs');
    }
};
