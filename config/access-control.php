<?php

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
    ],
];
