<?php

use App\Http\Controllers\Epasien\bridging\RencanaKontrolController;
use App\Http\Controllers\Epasien\DashboardController;
use App\Http\Controllers\Epasien\menu\DaftarOnlineController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\KamarController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\LaboratoriumController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\PoliklinikController;
use App\Http\Controllers\Epasien\menu\FasilitasTarif\RadiologiController;
use App\Http\Controllers\Epasien\menu\JadwalDokterController;
use App\Http\Controllers\Epasien\menu\PatientServiceController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\OperasiController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\PemeriksaanLaboratController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\PemeriksaanRadiologiController;
use App\Http\Controllers\Epasien\menu\PermintaanTindakan\ResepObatController;
use App\Http\Controllers\Epasien\menu\PromotionConfigurationController;
use App\Http\Controllers\Epasien\menu\PromotionController;
use App\Http\Controllers\Epasien\menu\RiwayatMcuController;
use App\Http\Controllers\Epasien\menu\RiwayatPemeriksaanController;
use App\Http\Controllers\Epasien\menu\Surat\SuratKontrolController;
use App\Http\Controllers\Epasien\menu\Surat\SuratRujukanController;
use App\Http\Controllers\Epasien\NotificationController;
use App\Http\Controllers\Epasien\PushSubscriptionController;
use App\Http\Controllers\Epasien\settings\auth\permissionsController;
use App\Http\Controllers\Epasien\settings\auth\rolesController;
use App\Http\Controllers\Epasien\settings\auth\usersController;
use App\Http\Controllers\Epasien\settings\DoctorPhotoController;
use App\Http\Controllers\Epasien\settings\DoctorScheduleController;
use App\Http\Controllers\Epasien\settings\RoleConfigurationController;
use App\Http\Controllers\LandingPage\LandingPageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('landingPage.index');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware([
    'auth',
    'verified',
    'permission:EPASIEN.MENU',
    'permission:EPASIEN.MENU.DASHBOARD',
])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/e-pasien/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/e-pasien/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::patch('/e-pasien/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/e-pasien/push/config', [PushSubscriptionController::class, 'config'])->name('push.config');
    Route::post('/e-pasien/push/subscriptions', [PushSubscriptionController::class, 'store'])->name('push.store');
    Route::delete('/e-pasien/push/subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('e-pasien/settings')
        ->middleware('permission:EPASIEN.SETTINGS')
        ->group(function () {
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

            // Konfigurasi fitur berbasis role
            Route::get('/role-configurations', [RoleConfigurationController::class, 'index'])
                ->middleware('role_or_permission:Super Admin|roles.update')
                ->name('roleConfiguration.index');
            Route::get('/role-configurations/users', [RoleConfigurationController::class, 'users'])
                ->middleware('role_or_permission:Super Admin|roles.update')
                ->name('roleConfiguration.users');
            Route::put('/role-configurations', [RoleConfigurationController::class, 'update'])
                ->middleware('role_or_permission:Super Admin|roles.update')
                ->name('roleConfiguration.update');

            // Landing Page & Data
            Route::middleware('permission:EPASIEN.SETTINGS.JADWAL_DOKTER')->group(function () {
                Route::get('/jadwal-dokter', [DoctorScheduleController::class, 'index'])
                    ->name('doctorScheduleSettings.index');
                Route::put('/jadwal-dokter', [DoctorScheduleController::class, 'update'])
                    ->name('doctorScheduleSettings.update');
                Route::get('/landing-page-data/foto-dokter', [DoctorPhotoController::class, 'index'])
                    ->name('doctorPhotoSettings.index');
                Route::post('/landing-page-data/foto-dokter', [DoctorPhotoController::class, 'update'])
                    ->name('doctorPhotoSettings.update');
                Route::delete('/landing-page-data/foto-dokter/{doctorCode}', [DoctorPhotoController::class, 'destroy'])
                    ->name('doctorPhotoSettings.destroy');
            });
        });

    Route::prefix('e-pasien/menu')
        ->middleware('permission:EPASIEN.MENU')
        ->group(function () {
            Route::middleware('permission:EPASIEN.MENU.PROMOSI')->group(function () {
                Route::get('/promo-sehat', [PromotionController::class, 'index'])->name('promotions.index');
                Route::get('/promo-sehat/{promotion}', [PromotionController::class, 'show'])
                    ->whereNumber('promotion')->name('promotions.show');

                Route::middleware('permission:EPASIEN.MENU.PROMOSI.KELOLA')->group(function () {
                    Route::get('/promo-sehat/konfigurasi', [PromotionConfigurationController::class, 'edit'])
                        ->name('promotions.configuration.edit');
                    Route::put('/promo-sehat/konfigurasi', [PromotionConfigurationController::class, 'update'])
                        ->name('promotions.configuration.update');
                    Route::get('/promo-sehat-baru', [PromotionController::class, 'create'])->name('promotions.create');
                    Route::post('/promo-sehat', [PromotionController::class, 'store'])->name('promotions.store');
                    Route::get('/promo-sehat/{promotion}/edit', [PromotionController::class, 'edit'])
                        ->whereNumber('promotion')->name('promotions.edit');
                    Route::put('/promo-sehat/{promotion}', [PromotionController::class, 'update'])
                        ->whereNumber('promotion')->name('promotions.update');
                    Route::patch('/promo-sehat/{promotion}/archive', [PromotionController::class, 'archive'])
                        ->whereNumber('promotion')->name('promotions.archive');
                    Route::patch('/promo-sehat/{promotion}/restore', [PromotionController::class, 'restore'])
                        ->whereNumber('promotion')->name('promotions.restore');
                    Route::delete('/promo-sehat/{promotion}', [PromotionController::class, 'destroy'])
                        ->whereNumber('promotion')->name('promotions.destroy');
                });
            });

            Route::middleware('permission:EPASIEN.MENU.PASIEN_SERVICE')->group(function () {
                Route::get('/pasien-service', [PatientServiceController::class, 'index'])
                    ->name('patientService.index');
                Route::get('/pasien-service/navbar', [PatientServiceController::class, 'navbar'])
                    ->name('patientService.navbar');
                Route::post('/pasien-service/deliveries', [PatientServiceController::class, 'markDelivered'])
                    ->middleware('throttle:60,1')
                    ->name('patientService.deliveries');
                Route::post('/pasien-service/conversations', [PatientServiceController::class, 'storeConversation'])
                    ->middleware('throttle:10,1')
                    ->name('patientService.conversations.store');
                Route::get('/pasien-service/conversations/{conversation}/messages', [PatientServiceController::class, 'messages'])
                    ->name('patientService.messages.index');
                Route::post('/pasien-service/conversations/{conversation}/messages', [PatientServiceController::class, 'storeMessage'])
                    ->middleware('throttle:30,1')
                    ->name('patientService.messages.store');
                Route::patch('/pasien-service/conversations/{conversation}/read', [PatientServiceController::class, 'markRead'])
                    ->name('patientService.read');
                Route::patch('/pasien-service/conversations/{conversation}/status', [PatientServiceController::class, 'updateStatus'])
                    ->name('patientService.status');
            });

            Route::middleware('permission:EPASIEN.MENU.JADWAL_DOKTER')->group(function () {
                Route::get('/jadwal-dokter', [JadwalDokterController::class, 'index'])
                    ->name('jadwalDokter.index');
            });

            Route::middleware('permission:EPASIEN.MENU.FASILITAS_TARIF')->group(function () {
                Route::get('/fasilitas-tarif/kamar', [KamarController::class, 'index'])
                    ->name('kamar.index');
                Route::get('/fasilitas-tarif/laboratorium', [LaboratoriumController::class, 'index'])
                    ->name('laboratorium.index');
                Route::get('/fasilitas-tarif/poliklinik', [PoliklinikController::class, 'index'])
                    ->name('poliklinik.index');
                Route::get('/fasilitas-tarif/radiologi', [RadiologiController::class, 'index'])
                    ->name('radiologi.index');
            });

            Route::middleware('permission:EPASIEN.MENU.PERMINTAAN_DAN_TINDAKAN')->group(function () {
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
            });

            Route::middleware('permission:EPASIEN.MENU.RIWAYAT_PEMERIKSAAN')->group(function () {
                Route::get('/riwayat-pemeriksaan', [RiwayatPemeriksaanController::class, 'index'])
                    ->name('riwayatPemeriksaan.index');
                Route::get('/riwayat-pemeriksaan/resume', [RiwayatPemeriksaanController::class, 'resume'])
                    ->name('riwayatPemeriksaan.resume');
                Route::get('/riwayat-pemeriksaan/pembayaran', [RiwayatPemeriksaanController::class, 'payment'])
                    ->name('riwayatPemeriksaan.payment');
            });

            Route::middleware('permission:EPASIEN.MENU.RIWAYAT_MCU')->group(function () {
                Route::get('/riwayat-mcu', [RiwayatMcuController::class, 'index'])
                    ->name('riwayatMcu.index');
                Route::get('/riwayat-mcu/detail', [RiwayatMcuController::class, 'detail'])
                    ->name('riwayatMcu.detail');
            });

            Route::middleware('permission:EPASIEN.MENU.SURAT')->group(function () {
                Route::get('/surat/surat-kontrol', [SuratKontrolController::class, 'index'])
                    ->name('suratKontrol.index');
                Route::get('/surat/surat-kontrol/bpjs', [SuratKontrolController::class, 'bpjs'])
                    ->name('suratKontrol.bpjs');
                Route::get('/surat/surat-rujukan', [SuratRujukanController::class, 'index'])
                    ->name('suratRujukan.index');
                Route::get('/surat/surat-rujukan/bpjs/masuk', [SuratRujukanController::class, 'incomingBpjs'])
                    ->name('suratRujukan.bpjs.masuk');
                Route::get('/surat/surat-rujukan/bpjs/keluar', [SuratRujukanController::class, 'outgoingBpjs'])
                    ->name('suratRujukan.bpjs.keluar');
            });

            Route::middleware('permission:EPASIEN.MENU.PENDAFTARAN_ONLINE')->group(function () {
                Route::get('/daftar-online', [DaftarOnlineController::class, 'index'])
                    ->name('daftarOnline.index');
                Route::get('/daftar-online/riwayat', [DaftarOnlineController::class, 'history'])
                    ->name('daftarOnline.history');
                Route::get('/daftar-online/jadwal', [DaftarOnlineController::class, 'schedules'])
                    ->name('daftarOnline.schedules');
                Route::get('/daftar-online/surat-kontrol', [RencanaKontrolController::class, 'index'])
                    ->name('daftarOnline.suratKontrol');
                Route::get('/daftar-online/surat-kontrol/{controlLetterNumber}', [RencanaKontrolController::class, 'show'])
                    ->name('daftarOnline.suratKontrol.show');
                Route::post('/daftar-online/antrean/preview', [DaftarOnlineController::class, 'previewAntrol'])
                    ->name('daftarOnline.antrol.preview');
                Route::post('/daftar-online/antrean/submit', [DaftarOnlineController::class, 'submitAntrol'])
                    ->name('daftarOnline.antrol.submit');
                Route::post('/daftar-online/store', [DaftarOnlineController::class, 'store'])
                    ->name('daftarOnline.store');
                Route::patch('/daftar-online/batal', [DaftarOnlineController::class, 'cancel'])
                    ->name('daftarOnline.cancel');
            });
        });
});

require __DIR__.'/auth.php';
