<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patient_queue_call_events')) {
            Schema::create('patient_queue_call_events', function (Blueprint $table): void {
                $table->id();
                $table->date('service_date');
                $table->string('visit_number', 30);
                $table->string('medical_record_number', 30);
                $table->string('queue_number', 20);
                $table->string('doctor_code', 20);
                $table->string('clinic_code', 15);
                $table->string('doctor_name')->nullable();
                $table->string('clinic_name')->nullable();
                $table->timestamp('called_at')->nullable();
                $table->timestamp('detected_at');
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->unsignedInteger('recipients_count')->default(0);
                $table->timestamps();

                $table->unique(
                    ['service_date', 'visit_number'],
                    'patient_queue_call_visit_unique'
                );
                $table->index(['service_date', 'queued_at']);
                $table->index(
                    ['medical_record_number', 'service_date'],
                    'patient_queue_mrn_date_idx'
                );
            });

            return;
        }

        if (! Schema::hasIndex('patient_queue_call_events', 'patient_queue_mrn_date_idx')) {
            Schema::table('patient_queue_call_events', function (Blueprint $table): void {
                $table->index(
                    ['medical_record_number', 'service_date'],
                    'patient_queue_mrn_date_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_queue_call_events');
    }
};
