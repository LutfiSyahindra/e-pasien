<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\DaftarOnlineService;
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
            $mock->shouldReceive('pendingRegistration')->once()->andReturnNull();
            $mock->shouldReceive('penjaminOptions')->once()->andReturn([
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
}
