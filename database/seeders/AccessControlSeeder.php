<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    /**
     * Ini untuk menyiapkan permission inti, role bawaan, dan akun administrator lokal.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(config('access-control.protected_permissions'))
            ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));

        $superAdmin = Role::findOrCreate(config('access-control.super_admin_role'), 'web');
        $administrator = Role::findOrCreate('Administrator', 'web');
        $patient = Role::findOrCreate(config('access-control.patient_role'), 'web');
        $marketing = Role::findOrCreate(config('access-control.marketing_role'), 'web');

        // Ini untuk memberi Administrator seluruh permission eksplisit yang tersedia.
        $administrator->syncPermissions($permissions);

        $patient->givePermissionTo([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PROMOSI',
            'EPASIEN.MENU.PASIEN_SERVICE',
        ]);

        $marketing->givePermissionTo([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PROMOSI',
            'EPASIEN.MENU.PROMOSI.KELOLA',
        ]);

        // Ini untuk menyediakan akun awal setelah database di-seed.
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@epasien.test'],
            [
                'name' => 'Administrator E-Pasien',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $admin->assignRole($superAdmin);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
