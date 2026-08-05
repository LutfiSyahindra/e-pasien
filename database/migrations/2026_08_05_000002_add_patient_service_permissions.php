<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const VIEW = 'EPASIEN.MENU.PASIEN_SERVICE';

    private const MANAGE = 'EPASIEN.MENU.PASIEN_SERVICE.KELOLA';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $menu = Permission::findOrCreate('EPASIEN.MENU', 'web');
        $view = Permission::findOrCreate(self::VIEW, 'web');
        $manage = Permission::findOrCreate(self::MANAGE, 'web');

        $patientNames = array_unique([
            config('access-control.patient_role', 'Patient'),
            ...config('access-control.patient_role_aliases', ['Pasien']),
        ]);

        Role::query()->whereIn('name', $patientNames)->get()
            ->each->givePermissionTo([$menu, $view]);

        Role::query()->whereIn('name', config('access-control.patient_service_admin_roles', ['Administrator', 'Admin']))->get()
            ->each->givePermissionTo([$menu, $view, $manage]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->whereIn('name', [self::VIEW, self::MANAGE])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
