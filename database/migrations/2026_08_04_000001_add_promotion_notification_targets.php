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

        if (! Schema::connection($connection)->hasColumn($rolesTable, 'promotion_notifications_enabled')) {
            Schema::connection($connection)->table($rolesTable, function (Blueprint $table): void {
                $table->boolean('promotion_notifications_enabled')
                    ->default(false)
                    ->after('email_onboarding_enabled');
            });
        }

        if (! Schema::connection($connection)->hasTable('promotion_notification_user_configurations')) {
            Schema::connection($connection)->create(
                'promotion_notification_user_configurations',
                function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                    $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->timestamps();
                }
            );
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
            ->update(['promotion_notifications_enabled' => true]);
    }

    public function down(): void
    {
        $connection = config('database.default');
        $rolesTable = config('permission.table_names.roles', 'roles');

        Schema::connection($connection)->dropIfExists('promotion_notification_user_configurations');

        if (Schema::connection($connection)->hasColumn($rolesTable, 'promotion_notifications_enabled')) {
            Schema::connection($connection)->table($rolesTable, function (Blueprint $table): void {
                $table->dropColumn('promotion_notifications_enabled');
            });
        }
    }
};
