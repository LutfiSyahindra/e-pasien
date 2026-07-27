<?php

namespace Tests\Feature;

use App\Models\OnlineRegistrationAudit;
use App\Models\RegistrationRoleConfiguration;
use App\Models\User;
use App\Repositories\epasien\menu\DaftarOnlineRepository;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
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
        $role = Role::create(['name' => 'Petugas BPJS', 'guard_name' => 'web']);
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
            'kd_pj' => 'BPJ',
            'png_jawab' => 'BPJS Kesehatan',
        ];

        $repository = $this->createMock(DaftarOnlineRepository::class);
        $repository->method('findPatient')->with('000123')->willReturn($patient);
        $repository->method('findPendingRegistration')->with('000123')->willReturn(null);
        $repository->method('findSchedule')->willReturn($schedule);
        $repository
            ->expects($this->once())
            ->method('findEligiblePenjamin')
            ->with('BPJ', true)
            ->willReturn($penjamin);
        $repository->method('createRegistration')->willReturn([
            'no_reg' => '001',
            'no_rawat' => '2026/07/27/000001',
            'tgl_registrasi' => '2026-07-27',
            'jam_reg' => '08:00:00',
            'kd_dokter' => 'D001',
            'kd_poli' => 'POL01',
            'kd_pj' => 'BPJ',
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
            'kd_pj' => 'BPJ',
            'no_peserta' => '0009998887776',
        ], true);

        $audit = OnlineRegistrationAudit::query()->sole();

        $this->assertSame('2026/07/27/000001', $audit->no_rawat);
        $this->assertSame('000123', $audit->patient_medical_record_number);
        $this->assertSame('BPJ', $audit->guarantor_code);
        $this->assertSame($user->id, $audit->registered_by_user_id);
        $this->assertSame(['Petugas BPJS'], $audit->registered_by_roles);
    }
}
