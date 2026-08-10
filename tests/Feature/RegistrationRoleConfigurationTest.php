<?php

namespace Tests\Feature;

use App\Models\OnlineRegistrationAudit;
use App\Models\RegistrationRoleConfiguration;
use App\Models\User;
use App\Repositories\epasien\menu\DaftarOnlineRepository;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
use App\Services\epasien\settings\RoleConfigurationService;
use PDO;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationRoleConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Driver PDO SQLite tidak tersedia pada lingkungan pengujian.');
        }

        $this->artisan('migrate:fresh');
    }

    public function test_configured_role_grants_registration_staff_access(): void
    {
        $user = User::factory()->create(['username' => 'PETUGAS01']);
        $role = Role::create(['name' => 'Petugas Pendaftaran', 'guard_name' => 'web']);
        $user->assignRole($role);

        $service = app(RegistrationRoleConfigurationService::class);

        $this->assertFalse($service->isConfigured($user));

        $service->sync([$role->id], $user);

        $this->assertTrue($service->isConfigured($user->fresh()));
        $this->assertDatabaseHas('registration_role_configurations', [
            'role_id' => $role->id,
            'configured_by' => $user->id,
        ]);
    }

    public function test_central_role_configuration_syncs_bpjs_and_email_onboarding_features(): void
    {
        $configuredBy = User::factory()->create(['username' => 'ADMIN01']);
        $bpjsRole = Role::create([
            'name' => 'Petugas BPJS',
            'guard_name' => 'web',
        ]);
        $patientRole = Role::create([
            'name' => 'Pasien',
            'guard_name' => 'web',
        ]);

        app(RoleConfigurationService::class)->sync(
            [$bpjsRole->id],
            [$patientRole->id],
            [$patientRole->id],
            [$configuredBy->id],
            [$bpjsRole->id],
            [$bpjsRole->id],
            [$patientRole->id],
            $configuredBy,
        );

        $this->assertDatabaseHas('registration_role_configurations', [
            'role_id' => $bpjsRole->id,
            'configured_by' => $configuredBy->id,
        ]);
        $this->assertDatabaseMissing('registration_role_configurations', [
            'role_id' => $patientRole->id,
        ]);
        $this->assertFalse((bool) $bpjsRole->fresh()->email_onboarding_enabled);
        $this->assertTrue((bool) $patientRole->fresh()->email_onboarding_enabled);
        $this->assertTrue((bool) $patientRole->fresh()->promotion_notifications_enabled);
        $this->assertTrue((bool) $patientRole->fresh()->doctor_arrival_notifications_enabled);
        $this->assertTrue($bpjsRole->fresh()->hasPermissionTo('EPASIEN.MENU.PROMOSI.KELOLA'));
        $this->assertTrue($bpjsRole->fresh()->hasPermissionTo('EPASIEN.MENU.PASIEN_SERVICE'));
        $this->assertTrue($bpjsRole->fresh()->hasPermissionTo('EPASIEN.MENU.PASIEN_SERVICE.KELOLA'));
        $this->assertDatabaseHas('promotion_notification_user_configurations', [
            'user_id' => $configuredBy->id,
            'configured_by' => $configuredBy->id,
        ]);
    }

    public function test_role_configuration_page_exposes_bpjs_and_email_feature_matrix(): void
    {
        $admin = User::factory()->create(['username' => 'ADMIN02']);
        $admin->assignRole(Role::create([
            'name' => config('access-control.super_admin_role', 'Super Admin'),
            'guard_name' => 'web',
        ]));
        Role::create([
            'name' => 'Pasien',
            'guard_name' => 'web',
            'email_onboarding_enabled' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('roleConfiguration.index'));

        $response
            ->assertOk()
            ->assertSeeText('Konfigurasi Roles')
            ->assertSeeText('Pendaftaran BPJS')
            ->assertSeeText('Animasi Onboarding Email')
            ->assertSeeText('Notifikasi Promosi & Informasi')
            ->assertSeeText('Notifikasi Dokter Datang')
            ->assertSeeText('Pengelola Promosi & Informasi')
            ->assertSeeText('Admin Pasien Service')
            ->assertSeeText('Seluruh pengguna aktif dari role terpilih menjadi Tim Pasien Service')
            ->assertSeeText('Pilih Pasien/User Tertentu')
            ->assertSee('id="roleConfigurationSearch"', false)
            ->assertSee('data-role-toggle', false)
            ->assertSee('name="registration_role_ids[]"', false)
            ->assertSee('name="email_onboarding_role_ids[]"', false)
            ->assertSee('name="promotion_notification_role_ids[]"', false)
            ->assertSee('name="doctor_arrival_notification_role_ids[]"', false)
            ->assertSee('name="promotion_management_role_ids[]"', false)
            ->assertSee('name="patient_service_role_ids[]"', false)
            ->assertSee('name="promotion_notification_user_ids[]"', false);
    }

    public function test_role_configuration_update_saves_promotion_roles_and_direct_users(): void
    {
        $admin = User::factory()->create(['username' => 'ADMIN03']);
        $admin->assignRole(Role::create([
            'name' => config('access-control.super_admin_role', 'Super Admin'),
            'guard_name' => 'web',
        ]));
        $patientRole = Role::create(['name' => 'Pasien Uji', 'guard_name' => 'web']);
        $serviceRole = Role::create(['name' => 'Customer Care', 'guard_name' => 'web']);
        $target = User::factory()->create(['name' => 'Pasien Target', 'status' => true]);

        $this->actingAs($admin)
            ->put(route('roleConfiguration.update'), [
                'promotion_notification_role_ids' => [$patientRole->id],
                'doctor_arrival_notification_role_ids' => [$patientRole->id],
                'promotion_notification_user_ids' => [$target->id],
                'promotion_management_role_ids' => [$serviceRole->id],
                'patient_service_role_ids' => [$serviceRole->id],
            ])
            ->assertRedirect(route('roleConfiguration.index'))
            ->assertSessionHas('status');

        $this->assertTrue((bool) $patientRole->fresh()->promotion_notifications_enabled);
        $this->assertTrue((bool) $patientRole->fresh()->doctor_arrival_notifications_enabled);
        $this->assertTrue($serviceRole->fresh()->hasPermissionTo('EPASIEN.MENU.PROMOSI'));
        $this->assertTrue($serviceRole->fresh()->hasPermissionTo('EPASIEN.MENU.PROMOSI.KELOLA'));
        $this->assertTrue($serviceRole->fresh()->hasPermissionTo('EPASIEN.MENU.PASIEN_SERVICE'));
        $this->assertTrue($serviceRole->fresh()->hasPermissionTo('EPASIEN.MENU.PASIEN_SERVICE.KELOLA'));
        $this->assertDatabaseHas('promotion_notification_user_configurations', [
            'user_id' => $target->id,
            'configured_by' => $admin->id,
        ]);
    }

    public function test_role_configuration_can_remove_patient_service_management_from_a_role(): void
    {
        $admin = User::factory()->create(['username' => 'ADMIN04']);
        $admin->assignRole(Role::create([
            'name' => config('access-control.super_admin_role', 'Super Admin'),
            'guard_name' => 'web',
        ]));
        $serviceRole = Role::create(['name' => 'Customer Service', 'guard_name' => 'web']);
        $serviceRole->givePermissionTo([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PASIEN_SERVICE',
            'EPASIEN.MENU.PASIEN_SERVICE.KELOLA',
        ]);

        $this->actingAs($admin)
            ->put(route('roleConfiguration.update'), ['patient_service_role_ids' => []])
            ->assertRedirect(route('roleConfiguration.index'));

        $this->assertFalse($serviceRole->fresh()->hasPermissionTo('EPASIEN.MENU.PASIEN_SERVICE.KELOLA'));
        $this->assertTrue($serviceRole->fresh()->hasPermissionTo('EPASIEN.MENU.PASIEN_SERVICE'));
    }

    public function test_role_configuration_can_remove_promotion_management_from_a_role(): void
    {
        $admin = User::factory()->create(['username' => 'ADMIN-CONTENT']);
        $admin->assignRole(Role::create([
            'name' => config('access-control.super_admin_role', 'Super Admin'),
            'guard_name' => 'web',
        ]));
        $contentRole = Role::create(['name' => 'Tim Konten', 'guard_name' => 'web']);
        $contentRole->givePermissionTo([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PROMOSI',
            'EPASIEN.MENU.PROMOSI.KELOLA',
        ]);

        $this->actingAs($admin)
            ->put(route('roleConfiguration.update'), ['promotion_management_role_ids' => []])
            ->assertRedirect(route('roleConfiguration.index'));

        $contentRole->refresh();
        $this->assertFalse($contentRole->hasPermissionTo('EPASIEN.MENU.PROMOSI.KELOLA'));
        $this->assertTrue($contentRole->hasPermissionTo('EPASIEN.MENU.PROMOSI'));
    }

    public function test_patient_role_cannot_be_configured_as_patient_service_handler(): void
    {
        $admin = User::factory()->create(['username' => 'ADMIN05']);
        $admin->assignRole(Role::create([
            'name' => config('access-control.super_admin_role', 'Super Admin'),
            'guard_name' => 'web',
        ]));
        $patientRole = Role::create([
            'name' => config('access-control.patient_role', 'Patient'),
            'guard_name' => 'web',
        ]);

        $this->actingAs($admin)
            ->from(route('roleConfiguration.index'))
            ->put(route('roleConfiguration.update'), [
                'patient_service_role_ids' => [$patientRole->id],
            ])
            ->assertRedirect(route('roleConfiguration.index'))
            ->assertSessionHasErrors('patient_service_role_ids');

        $this->assertFalse($patientRole->fresh()->hasPermissionTo('EPASIEN.MENU.PASIEN_SERVICE.KELOLA'));
    }

    public function test_registration_by_configured_role_is_audited_in_application_database(): void
    {
        $user = User::factory()->create([
            'name' => 'Petugas Satu',
            'username' => 'PETUGAS01',
        ]);
        $role = Role::create(['name' => 'Petugas BPJS', 'guard_name' => 'web']);
        $user->assignRole($role);

        RegistrationRoleConfiguration::query()->create([
            'role_id' => $role->id,
            'configured_by' => $user->id,
        ]);

        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'tgl_lahir' => '1990-01-01',
            'alamat' => 'Jl. Sehat',
            'keluarga' => 'AYAH',
            'namakeluarga' => 'Santoso',
            'no_peserta' => '0001112223334',
        ];
        $schedule = (object) [
            'nm_dokter' => 'dr. Budi',
            'nm_poli' => 'Poli Umum',
        ];
        $penjamin = (object) [
            'kd_pj' => 'UMU',
            'png_jawab' => 'Umum',
        ];

        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->method('findPatient')->with('000123')->willReturn($patient);
        $repository->method('findPendingRegistration')->with('000123')->willReturn(null);
        $repository->method('findSchedule')->willReturn($schedule);
        $repository
            ->expects($this->once())
            ->method('findEligiblePenjamin')
            ->with('UMU', true)
            ->willReturn($penjamin);
        $repository->method('createRegistration')->willReturn([
            'no_reg' => '001',
            'no_rawat' => '2026/07/27/000001',
            'tgl_registrasi' => '2026-07-27',
            'jam_reg' => '08:00:00',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'UMU',
            'stts' => 'Belum',
            'status_bayar' => 'Belum Bayar',
            'umurdaftar' => 36,
            'sttsumur' => 'Th',
        ]);

        $service = new DaftarOnlineService($repository);
        $service->register($user, [
            'no_rkm_medis' => '000123',
            'tgl_registrasi' => '2026-07-27',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'UMU',
        ], true);

        $audit = OnlineRegistrationAudit::query()->sole();

        $this->assertSame('2026/07/27/000001', $audit->no_rawat);
        $this->assertSame('000123', $audit->patient_medical_record_number);
        $this->assertSame('UMU', $audit->guarantor_code);
        $this->assertSame($user->id, $audit->registered_by_user_id);
        $this->assertSame(['Petugas BPJS'], $audit->registered_by_roles);
    }
}
