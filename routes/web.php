<?php

use App\Http\Controllers\Epasien\bridging\RencanaKontrolController;
use App\Http\Controllers\Epasien\menu\DaftarOnlineController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\KamarController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\LaboratoriumController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\PoliklinikController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\RadiologiController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\OperasiController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\PemeriksaanLaboratController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\PemeriksaanRadiologiController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\ResepObatController;
use App\Http\Controllers\Epasien\menu\RiwayatMcuController;
use App\Http\Controllers\Epasien\menu\RiwayatPemeriksaanController;
use App\Http\Controllers\Epasien\settings\auth\permissionsController;
use App\Http\Controllers\Epasien\settings\auth\rolesController;
use App\Http\Controllers\Epasien\settings\auth\usersController;
use App\Http\Controllers\Epasien\settings\RegistrationRoleConfigurationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landingPage.landingPage');
});

Route::get('/dashboard', function () {
    return view('e-pasien.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('e-pasien/settings')->group(function () {
        // Users
        Route::get('/auth/users', [usersController::class, 'users'])->name('users.users');
        Route::get('/users/tableUsers', [usersController::class, 'table'])->name('users.table');
        Route::put('/users/updateStatus', [usersController::class, 'updateStatus'])->name('users.updateStatus');
        Route::get('/users/getBranches', [usersController::class, 'getBranches'])->name('users.getBranches');
        Route::post('/users/sync-pasien', [usersController::class, 'syncPasienUsers'])->name('users.syncPasien');
        Route::post('/users/sync-pasien/stop', [usersController::class, 'stopSyncPasienUsers'])->name('users.stopSyncPasien');
        Route::get('/users/sync-pasien/status', [usersController::class, 'syncPasienUsersStatus'])->name('users.syncPasienStatus');
        Route::post('/users/store', [usersController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [usersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}/update', [usersController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}/delete', [usersController::class, 'destroy'])->name('users.delete');
        Route::get('/users/dataRoles', [usersController::class, 'dataRoles'])->name('users.dataRoles');
        Route::post('/users/assignRoles', [usersController::class, 'assignRoles'])->name('users.assignRoles');
        Route::get('/users/{id}/getUserRoles', [usersController::class, 'getUserRoles'])->name('users.getUserRoles');

        // Roles
        Route::get('/auth/roles', [rolesController::class, 'roles'])->name('roles.roles');
        Route::get('/roles/tableRoles', [rolesController::class, 'table'])->name('roles.table');
        Route::post('/roles/store', [rolesController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}/edit', [rolesController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}/update', [rolesController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}/delete', [rolesController::class, 'destroy'])->name('roles.delete');
        Route::get('/roles/dataPermissions', [rolesController::class, 'dataPermissions'])->name('roles.dataPermissions');
        Route::post('/roles/assignPermissions', [rolesController::class, 'assignPermissions'])->name('roles.assignPermissions');
        Route::get('/roles/{id}/getRolePermissions', [rolesController::class, 'getRolePermissions'])->name('roles.getRolePermissions');

        // Permissions
        Route::get('/auth/permissions', [permissionsController::class, 'permissions'])->name('permissions.permissions');
        Route::get('/permissions/tablePermissions', [permissionsController::class, 'table'])->name('permissions.table');
        Route::post('/permissions/store', [permissionsController::class, 'store'])->name('permissions.store');
        Route::get('/permissions/{id}/edit', [permissionsController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{id}/update', [permissionsController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{id}/delete', [permissionsController::class, 'destroy'])->name('permissions.delete');

        // Konfigurasi role pendaftaran BPJS
        Route::get('/registration-roles', [RegistrationRoleConfigurationController::class, 'index'])
            ->middleware('role_or_permission:Super Admin|roles.update')
            ->name('registrationRoleConfiguration.index');
        Route::put('/registration-roles', [RegistrationRoleConfigurationController::class, 'update'])
            ->middleware('role_or_permission:Super Admin|roles.update')
            ->name('registrationRoleConfiguration.update');
    });

    Route::prefix('e-pasien/menu')->group(function () {
        Route::get('/fasilitas-tarif/kamar', [KamarController::class, 'index'])
            ->name('kamar.index');
        Route::get('/fasilitas-tarif/laboratorium', [LaboratoriumController::class, 'index'])
            ->name('laboratorium.index');
        Route::get('/fasilitas-tarif/poliklinik', [PoliklinikController::class, 'index'])
            ->name('poliklinik.index');
        Route::get('/fasilitas-tarif/radiologi', [RadiologiController::class, 'index'])
            ->name('radiologi.index');
        Route::get('/permintaan-tindakan/pemeriksaan-laborat', [PemeriksaanLaboratController::class, 'index'])
            ->name('pemeriksaanLaborat.index');
        Route::get('/permintaan-tindakan/pemeriksaan-laborat/hasil', [PemeriksaanLaboratController::class, 'result'])
            ->name('pemeriksaanLaborat.result');
        Route::get('/permintaan-tindakan/pemeriksaan-radiologi', [PemeriksaanRadiologiController::class, 'index'])
            ->name('pemeriksaanRadiologi.index');
        Route::get('/permintaan-tindakan/pemeriksaan-radiologi/hasil', [PemeriksaanRadiologiController::class, 'result'])
            ->name('pemeriksaanRadiologi.result');
        Route::get('/permintaan-tindakan/resep-obat', [ResepObatController::class, 'index'])
            ->name('resepObat.index');
        Route::get('/permintaan-tindakan/operasi', [OperasiController::class, 'index'])
            ->name('operasi.index');
        Route::get('/permintaan-tindakan/operasi/detail', [OperasiController::class, 'detail'])
            ->name('operasi.detail');
        Route::get(
            '/permintaan-tindakan/pemeriksaan-radiologi/{noorder}/gambar/{image}',
            [PemeriksaanRadiologiController::class, 'image']
        )
            ->whereNumber('image')
            ->name('pemeriksaanRadiologi.image');
        Route::get('/riwayat-pemeriksaan', [RiwayatPemeriksaanController::class, 'index'])
            ->name('riwayatPemeriksaan.index');
        Route::get('/riwayat-pemeriksaan/resume', [RiwayatPemeriksaanController::class, 'resume'])
            ->name('riwayatPemeriksaan.resume');
        Route::get('/riwayat-pemeriksaan/pembayaran', [RiwayatPemeriksaanController::class, 'payment'])
            ->name('riwayatPemeriksaan.payment');
        Route::get('/riwayat-mcu', [RiwayatMcuController::class, 'index'])
            ->name('riwayatMcu.index');
        Route::get('/riwayat-mcu/detail', [RiwayatMcuController::class, 'detail'])
            ->name('riwayatMcu.detail');
        Route::get('/daftar-online', [DaftarOnlineController::class, 'index'])->name('daftarOnline.index');
        Route::get('/daftar-online/riwayat', [DaftarOnlineController::class, 'history'])->name('daftarOnline.history');
        Route::get('/daftar-online/jadwal', [DaftarOnlineController::class, 'schedules'])->name('daftarOnline.schedules');
        Route::get('/daftar-online/surat-kontrol', [RencanaKontrolController::class, 'index'])
            ->name('daftarOnline.suratKontrol');
        Route::get('/daftar-online/surat-kontrol/{controlLetterNumber}', [RencanaKontrolController::class, 'show'])
            ->name('daftarOnline.suratKontrol.show');
        Route::post('/daftar-online/antrean/preview', [DaftarOnlineController::class, 'previewAntrol'])
            ->name('daftarOnline.antrol.preview');
        Route::post('/daftar-online/antrean/submit', [DaftarOnlineController::class, 'submitAntrol'])
            ->name('daftarOnline.antrol.submit');
        Route::post('/daftar-online/store', [DaftarOnlineController::class, 'store'])->name('daftarOnline.store');
        Route::patch('/daftar-online/batal', [DaftarOnlineController::class, 'cancel'])
            ->name('daftarOnline.cancel');
    });
});

require __DIR__.'/auth.php';
