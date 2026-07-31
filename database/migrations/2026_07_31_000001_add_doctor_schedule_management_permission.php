<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSION = 'EPASIEN.SETTINGS.JADWAL_DOKTER';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate(self::PERMISSION, 'web');
        $settingsRoles = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query): void {
                $query
                    ->where('name', 'Administrator')
                    ->orWhereHas('permissions', fn ($query) => $query
                        ->where('name', 'EPASIEN.SETTINGS')
                        ->where('guard_name', 'web'));
            })
            ->get();

        foreach ($settingsRoles as $role) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
