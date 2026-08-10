<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $rolesTable = config('permission.table_names.roles', 'roles');

        if (! Schema::connection($connection)->hasColumn($rolesTable, 'doctor_arrival_notifications_enabled')) {
            Schema::connection($connection)->table($rolesTable, function (Blueprint $table): void {
                $table->boolean('doctor_arrival_notifications_enabled')
                    ->default(false)
                    ->after('promotion_notifications_enabled');
            });
        }

        if (! Schema::connection($connection)->hasTable('doctor_arrival_events')) {
            Schema::connection($connection)->create('doctor_arrival_events', function (Blueprint $table): void {
                $table->id();
                $table->date('service_date');
                $table->string('doctor_code', 20);
                $table->string('clinic_code', 15);
                $table->string('doctor_name')->nullable();
                $table->string('clinic_name')->nullable();
                $table->timestamp('detected_at');
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->unsignedInteger('recipients_count')->default(0);
                $table->timestamps();

                $table->unique(
                    ['service_date', 'doctor_code', 'clinic_code'],
                    'doctor_arrival_schedule_unique'
                );
                $table->index(['service_date', 'queued_at']);
            });
        }

        $patientRoleNames = collect([
            config('access-control.patient_role', 'Patient'),
            ...config('access-control.patient_role_aliases', ['Pasien']),
        ])
            ->filter(fn (mixed $role): bool => is_string($role) && trim($role) !== '')
            ->map(fn (string $role): string => trim($role))
            ->unique()
            ->values();

        DB::connection($connection)
            ->table($rolesTable)
            ->where('guard_name', 'web')
            ->whereIn('name', $patientRoleNames)
            ->update(['doctor_arrival_notifications_enabled' => true]);
    }

    public function down(): void
    {
        $connection = config('database.default');
        $rolesTable = config('permission.table_names.roles', 'roles');

        Schema::connection($connection)->dropIfExists('doctor_arrival_events');

        if (Schema::connection($connection)->hasColumn($rolesTable, 'doctor_arrival_notifications_enabled')) {
            Schema::connection($connection)->table($rolesTable, function (Blueprint $table): void {
                $table->dropColumn('doctor_arrival_notifications_enabled');
            });
        }
    }
};
