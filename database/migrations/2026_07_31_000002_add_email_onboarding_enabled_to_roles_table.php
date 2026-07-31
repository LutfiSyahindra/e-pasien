<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');

        if (! Schema::hasColumn($rolesTable, 'email_onboarding_enabled')) {
            Schema::table($rolesTable, function (Blueprint $table): void {
                $table->boolean('email_onboarding_enabled')
                    ->default(false)
                    ->after('guard_name');
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

        DB::table($rolesTable)
            ->where('guard_name', 'web')
            ->whereIn('name', $patientRoleNames)
            ->update(['email_onboarding_enabled' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');

        if (Schema::hasColumn($rolesTable, 'email_onboarding_enabled')) {
            Schema::table($rolesTable, function (Blueprint $table): void {
                $table->dropColumn('email_onboarding_enabled');
            });
        }
    }
};
