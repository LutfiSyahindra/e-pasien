<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class DaftarOnlinePageTest extends TestCase
{
    public function test_new_registration_page_shows_guarantor_notice_and_visit_fields(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_tlp' => '08123456789',
            'alamat' => 'Jl. Sehat',
        ];

        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($patient): void {
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('pendingRegistrationForMedicalRecord')->once()->with('000123')->andReturnNull();
            $mock->shouldReceive('penjaminOptions')->once()->with(false)->andReturn([
                ['kd_pj' => 'A01', 'png_jawab' => 'Asuransi Sehat'],
            ]);
        });

        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index'));

        $response
            ->assertOk()
            ->assertSee('id="onlineRegistrationNoticeModal"', false)
            ->assertSeeText('UMUM')
            ->assertSeeText('Asuransi selain BPJS Kesehatan')
            ->assertSeeText('Silakan lakukan pendaftaran melalui aplikasi Mobile JKN.')
            ->assertSee('class="online-choice-flow"', false)
            ->assertSee('id="kd_poli"', false)
            ->assertSee('id="kd_dokter"', false)
            ->assertSee('id="kd_pj"', false)
            ->assertSee('dropdownParent: field', false)
            ->assertSee('showBootstrapModal(elements.noticeModal);', false);
    }

    public function test_configured_registration_role_can_select_patient_and_bpjs_guarantor(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000456',
            'nm_pasien' => 'Siti',
            'no_tlp' => '08120000000',
            'alamat' => 'Jl. Mawar',
        ];

        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock) use ($patient): void {
            $mock->shouldReceive('patientForMedicalRecord')->once()->with('000456')->andReturn($patient);
            $mock->shouldReceive('pendingRegistrationForMedicalRecord')->once()->with('000456')->andReturnNull();
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([
                ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS Kesehatan'],
            ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.index', [
            'no_rkm_medis' => '000456',
        ]));

        $response
            ->assertOk()
            ->assertSeeText('Role Anda dapat mendaftarkan pasien lain')
            ->assertSeeText('BPJS Kesehatan tersedia untuk role Anda.')
            ->assertSee('value="000456"', false)
            ->assertSeeText('Siti');
    }

    public function test_configured_registration_role_sees_all_history_and_guarantor_filter(): void
    {
        $this->mock(RegistrationRoleConfigurationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
        });
        $this->mock(DaftarOnlineService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('registrationHistory')->once()->andReturn(
                new LengthAwarePaginator([], 0, 8)
            );
            $mock->shouldReceive('penjaminOptions')->once()->with(true)->andReturn([
                ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS Kesehatan'],
                ['kd_pj' => 'UMU', 'png_jawab' => 'Umum'],
            ]);
        });

        $user = new User([
            'name' => 'Petugas',
            'username' => 'PETUGAS01',
            'email' => 'petugas@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        $response = $this->actingAs($user)->get(route('daftarOnline.history'));

        $response
            ->assertOk()
            ->assertSeeText('Semua Pasien')
            ->assertSee('name="kd_pj"', false)
            ->assertSeeText('Semua Penjamin')
            ->assertSeeText('BPJS Kesehatan');
    }
}
