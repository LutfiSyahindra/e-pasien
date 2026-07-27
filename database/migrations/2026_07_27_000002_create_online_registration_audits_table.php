<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.default'))
            ->create('online_registration_audits', function (Blueprint $table): void {
                $table->id();
                $table->string('no_rawat', 30)->unique();
                $table->string('no_reg', 10);
                $table->date('registration_date')->index();
                $table->time('registration_time')->nullable();
                $table->string('patient_medical_record_number', 20)->index();
                $table->string('patient_name', 100);
                $table->string('doctor_code', 20);
                $table->string('doctor_name', 100);
                $table->string('clinic_code', 15);
                $table->string('clinic_name', 100);
                $table->string('guarantor_code', 10)->index();
                $table->string('guarantor_name', 100);
                $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('registered_by_name', 100);
                $table->string('registered_by_username', 100)->nullable();
                $table->json('registered_by_roles')->nullable();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('database.default'))
            ->dropIfExists('online_registration_audits');
    }
};
