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

        Role::query()
            ->whereIn('name', config('access-control.patient_service_admin_roles', ['Administrator', 'Admin']))
            ->get()
            ->each->givePermissionTo([$menu, $view, $manage]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()
            ->whereIn('name', config('access-control.patient_service_admin_roles', ['Administrator', 'Admin']))
            ->get()
            ->each(function (Role $role): void {
                $role->revokePermissionTo([self::VIEW, self::MANAGE]);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
