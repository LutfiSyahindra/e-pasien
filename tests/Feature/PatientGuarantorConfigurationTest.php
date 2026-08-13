<?php

namespace Tests\Feature;

use App\Models\PatientGuarantorConfiguration;
use App\Models\User;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
use Mockery\MockInterface;
use PDO;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientGuarantorConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Driver PDO SQLite tidak tersedia pada lingkungan pengujian.');
        }

        $this->artisan('migrate:fresh');
    }

    public function test_authorized_staff_can_view_and_save_patient_guarantors(): void
    {
        $guarantors = [
            ['kd_pj' => 'UMU', 'png_jawab' => 'Umum'],
            ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS Kesehatan'],
            ['kd_pj' => 'A01', 'png_jawab' => 'Asuransi Sehat'],
        ];

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($guarantors): void {
            $mock->shouldReceive('penjaminOptions')->twice()->with(true)->andReturn($guarantors);
        });

        $staff = $this->superAdmin();

        $this->actingAs($staff)
            ->get(route('patientGuarantorSettings.index'))
            ->assertOk()
            ->assertViewIs('e-pasien.settings.patientGuarantors.index')
            ->assertSeeText('Konfigurasi Penjamin Pasien')
            ->assertSee('value="UMU"', false)
            ->assertSee('value="BPJ"', false)
            ->assertSee('patient-guarantor-settings.css');

        $this->actingAs($staff)
            ->put(route('patientGuarantorSettings.update'), [
                'guarantor_codes' => ['UMU', 'A01'],
            ])
            ->assertRedirect(route('patientGuarantorSettings.index'))
            ->assertSessionHas('status');

        $configuration = PatientGuarantorConfiguration::query()->firstOrFail();

        $this->assertSame(['UMU', 'A01'], $configuration->allowed_guarantor_codes);
        $this->assertSame($staff->id, $configuration->configured_by);
    }

    public function test_configuration_rejects_unknown_or_empty_guarantors(): void
    {
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([
                ['kd_pj' => 'UMU', 'png_jawab' => 'Umum'],
            ]);
        });

        $staff = $this->superAdmin();

        $this->actingAs($staff)
            ->from(route('patientGuarantorSettings.index'))
            ->put(route('patientGuarantorSettings.update'), [
                'guarantor_codes' => ['PALSU'],
            ])
            ->assertRedirect(route('patientGuarantorSettings.index'))
            ->assertSessionHasErrors('guarantor_codes');

        $this->actingAs($staff)
            ->from(route('patientGuarantorSettings.index'))
            ->put(route('patientGuarantorSettings.update'), [])
            ->assertRedirect(route('patientGuarantorSettings.index'))
            ->assertSessionHasErrors('guarantor_codes');

        $this->assertDatabaseCount('patient_guarantor_configurations', 0);
    }

    public function test_patient_registration_page_only_shows_configured_guarantors(): void
    {
        $patient = User::factory()->create([
            'username' => '000123',
            'status' => true,
        ]);
        PatientGuarantorConfiguration::query()->create([
            'key' => PatientGuarantorConfiguration::DEFAULT_KEY,
            'allowed_guarantor_codes' => ['UMU'],
            'configured_by' => null,
        ]);

        $this->withoutMiddleware(PermissionMiddleware::class);
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnFalse();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('patientForUser')->once()->andReturn((object) [
                'no_rkm_medis' => '000123',
                'nm_pasien' => 'Budi',
            ]);
            $mock->shouldReceive('pendingRegistrationForMedicalRecord')->once()->andReturnNull();
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([
                ['kd_pj' => 'UMU', 'png_jawab' => 'Umum'],
                ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS Kesehatan'],
            ]);
        });

        $this->actingAs($patient)
            ->get(route('daftarOnline.index'))
            ->assertOk()
            ->assertSee('<option value="UMU"', false)
            ->assertDontSee('<option value="BPJ"', false);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['status' => true]);
        $role = Role::findOrCreate(config('access-control.super_admin_role'), 'web');
        $user->assignRole($role);

        return $user;
    }
}
