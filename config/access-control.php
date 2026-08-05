<?php

$sidebarPermissions = [
    'EPASIEN.SETTINGS',
    'EPASIEN.SETTINGS.JADWAL_DOKTER',
    'EPASIEN.MENU',
    'EPASIEN.MENU.DASHBOARD',
    'EPASIEN.MENU.JADWAL_DOKTER',
    'EPASIEN.MENU.RIWAYAT_PEMERIKSAAN',
    'EPASIEN.MENU.RIWAYAT_MCU',
    'EPASIEN.MENU.SURAT',
    'EPASIEN.MENU.PERMINTAAN_DAN_TINDAKAN',
    'EPASIEN.MENU.FASILITAS_TARIF',
    'EPASIEN.MENU.PENDAFTARAN_ONLINE',
    'EPASIEN.MENU.PASIEN_SERVICE',
];

return [
    /*
    |--------------------------------------------------------------------------
    | Pengaturan inti kontrol akses
    |--------------------------------------------------------------------------
    |
    | Nama berikut dipakai oleh service, seeder, dan antarmuka. Menaruhnya
    | pada satu tempat mencegah aturan penting tersebar di banyak file.
    |
    */

    'super_admin_role' => 'Super Admin',

    'patient_role' => 'Patient',

    'patient_role_aliases' => [
        'Pasien',
    ],

    'marketing_role' => 'Marketing',

    'marketing_role_aliases' => [
        'Pemasaran',
    ],

    'patient_service_admin_roles' => [
        'Administrator',
        'Admin',
    ],

    'sidebar_permissions' => $sidebarPermissions,

    'protected_permissions' => [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.view',
        'permissions.create',
        'permissions.update',
        'permissions.delete',
        'EPASIEN.MENU.PROMOSI',
        'EPASIEN.MENU.PROMOSI.KELOLA',
        'EPASIEN.MENU.PASIEN_SERVICE.KELOLA',
        ...$sidebarPermissions,
    ],
];
