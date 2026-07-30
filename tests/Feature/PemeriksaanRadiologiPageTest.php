<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\PemeriksaanRadiologiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class PemeriksaanRadiologiPageTest extends TestCase
{
    public function test_guest_cannot_access_radiology_routes(): void
    {
        $this->get(route('pemeriksaanRadiologi.index'))
            ->assertRedirect(route('login'));
        $this->get(route('pemeriksaanRadiologi.result', [
            'noorder' => 'PR001',
        ]))->assertRedirect(route('login'));
        $this->get(route('pemeriksaanRadiologi.image', [
            'noorder' => 'PR001',
            'image' => 0,
        ]))->assertRedirect(route('login'));
    }

    public function test_patient_can_view_owned_radiology_requests(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi Santoso',
        ];
        $requests = new LengthAwarePaginator([[
            'noorder' => 'PR20260729001',
            'no_rawat' => '2026/07/29/000001',
            'tanggal_permintaan' => '2026-07-29',
            'tanggal_permintaan_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_permintaan' => '08:15',
            'hari_short' => 'Rab',
            'tanggal_angka' => '29',
            'bulan_short' => 'Jul',
            'tanggal_pemeriksaan' => '2026-07-29',
            'tanggal_pemeriksaan_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_pemeriksaan' => '08:45',
            'tanggal_hasil' => '2026-07-29',
            'tanggal_hasil_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_hasil' => '10:00',
            'status' => 'selesai',
            'status_label' => 'Hasil Tersedia',
            'status_icon' => 'bi-check-circle',
            'status_layanan' => 'ralan',
            'jenis_layanan' => 'Rawat Jalan',
            'layanan_tone' => 'ralan',
            'dokter_perujuk' => 'dr. Sehat',
            'poli' => 'Poli Umum',
            'diagnosa_klinis' => 'Batuk',
            'informasi_tambahan' => 'Evaluasi paru',
            'jumlah_pemeriksaan' => 1,
            'jumlah_hasil' => 1,
            'jumlah_gambar' => 2,
            'hasil_tersedia' => true,
            'pemeriksaan_diminta' => [[
                'kode' => 'RAD001',
                'nama' => 'Thorax PA Dewasa',
                'status_bayar' => 'Belum',
            ]],
        ]], 1, 8);

        $this->mock(PemeriksaanRadiologiService::class, function (
            MockInterface $mock
        ) use ($patient, $requests): void {
            $mock->shouldReceive('emptyCounts')->once()->andReturn([
                'all' => 1,
                'menunggu' => 0,
                'proses' => 0,
                'selesai' => 1,
            ]);
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('requestsForUser')
                ->once()
                ->withArgs(
                    fn (
                        User $user,
                        ?string $resultStatus,
                        ?string $careType,
                        ?string $startDate,
                        ?string $endDate,
                        ?string $search
                    ): bool => $user->username === '000123'
                        && $resultStatus === 'selesai'
                        && $careType === 'ralan'
                        && $startDate === '2026-07-01'
                        && $endDate === '2026-07-31'
                        && $search === 'thorax'
                )
                ->andReturn($requests);
            $mock->shouldReceive('countsForUser')->once()->andReturn([
                'all' => 1,
                'menunggu' => 0,
                'proses' => 0,
                'selesai' => 1,
            ]);
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('pemeriksaanRadiologi.index', [
                'status_hasil' => 'selesai',
                'status_layanan' => 'ralan',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-07-31',
                'q' => 'thorax',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.pemeriksaanRadiologi.index')
            ->assertSeeText('Pemeriksaan Radiologi')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('PR20260729001')
            ->assertSeeText('Thorax PA Dewasa')
            ->assertSeeText('2 gambar')
            ->assertSee('id="radiologyResultModal"', false)
            ->assertSee('id="radiologyImageViewer"', false)
            ->assertSee('data-mobile-filter-toggle', false)
            ->assertSee('id="radiologyFilterPanel"', false)
            ->assertSeeText('5 aktif')
            ->assertDontSee('target = "_blank"', false)
            ->assertSee('data-result-url=', false);
    }

    public function test_result_endpoint_hides_an_order_owned_by_another_patient(): void
    {
        $this->mock(PemeriksaanRadiologiService::class, function (
            MockInterface $mock
        ): void {
            $mock->shouldReceive('resultForUser')->once()->andReturnNull();
        });

        $this->actingAs($this->patientUser())
            ->getJson(route('pemeriksaanRadiologi.result', [
                'noorder' => 'PR-MILIK-ORANG-LAIN',
            ]))
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Permintaan radiologi tidak ditemukan.'
            );
    }

    public function test_owned_radiology_image_is_proxied_from_configured_server(): void
    {
        config()->set(
            'services.radiology.image_base_url',
            'http://192.168.2.9/webapps/radiologi'
        );
        Http::fake([
            '192.168.2.9/*' => Http::response(
                'fake-image-bytes',
                200,
                ['Content-Type' => 'image/jpeg']
            ),
        ]);

        $this->mock(PemeriksaanRadiologiService::class, function (
            MockInterface $mock
        ): void {
            $mock->shouldReceive('imageForUser')
                ->once()
                ->andReturn([
                    'path' => 'pages/upload/radiologi-01.jpg',
                    'filename' => 'radiologi-01.jpg',
                ]);
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('pemeriksaanRadiologi.image', [
                'noorder' => 'PR20260729001',
                'image' => 0,
            ])
        );

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertSee('fake-image-bytes');

        Http::assertSent(
            fn ($request): bool => $request->url()
                === 'http://192.168.2.9/webapps/radiologi/pages/upload/radiologi-01.jpg'
        );
    }

    private function patientUser(): User
    {
        $user = new User([
            'name' => 'Budi Santoso',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        return $user;
    }
}
