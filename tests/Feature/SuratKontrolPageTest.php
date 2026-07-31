<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\epasien\menu\Surat\SuratKontrolRepository;
use App\Services\epasien\bridging\RencanaKontrolService;
use App\Services\epasien\menu\Surat\SuratKontrolService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SuratKontrolPageTest extends TestCase
{
    public function test_page_shows_general_and_bpjs_control_letter_experience(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_peserta' => '0002035874204',
        ];
        $letters = new LengthAwarePaginator([
            [
                'id' => 'letter-1',
                'tahun' => '2026',
                'no_rkm_medis' => '000123',
                'diagnosa' => 'Kontrol pasca rawat',
                'terapi' => 'Lanjutkan obat',
                'alasan1' => 'Evaluasi kondisi',
                'alasan2' => '',
                'rtl1' => 'Kontrol ulang',
                'rtl2' => '',
                'tanggal_datang' => [
                    'raw' => '2026-08-06 08:00:00',
                    'iso_date' => '2026-08-06',
                    'date_label' => '06 Agustus 2026',
                    'short_label' => '06 Agu 2026',
                    'time_label' => '08:00 WIB',
                ],
                'tanggal_rujukan' => [
                    'raw' => '2026-07-30 09:00:00',
                    'iso_date' => '2026-07-30',
                    'date_label' => '30 Juli 2026',
                    'short_label' => '30 Jul 2026',
                    'time_label' => '09:00 WIB',
                ],
                'kd_dokter' => 'D001',
                'nama_dokter' => 'dr. Sehat',
                'status' => 'Menunggu',
                'status_tone' => 'waiting',
                'is_upcoming' => true,
            ],
        ], 1, 8, 1);

        $this->mock(SuratKontrolService::class, function (MockInterface $mock) use (
            $patient,
            $letters
        ): void {
            $mock->shouldReceive('emptyCounts')->once()->andReturn([
                'all' => 0,
                'waiting' => 0,
                'examined' => 0,
                'cancelled' => 0,
            ]);
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('generalLettersForUser')
                ->once()
                ->with(Mockery::type(User::class), null)
                ->andReturn($letters);
            $mock->shouldReceive('generalCountsForUser')->once()->andReturn([
                'all' => 1,
                'waiting' => 1,
                'examined' => 0,
                'cancelled' => 0,
            ]);
        });

        $response = $this->actingAs($this->patientUser())
            ->get(route('suratKontrol.index'));

        $response
            ->assertOk()
            ->assertSeeText('Surat Kontrol UMUM')
            ->assertSeeText('Surat Kontrol BPJS')
            ->assertSeeText('Tidak perlu mengingat bulan kontrol')
            ->assertSeeText('3 bulan lalu sampai 3 bulan ke depan')
            ->assertSeeText('Kontrol pasca rawat')
            ->assertSeeText('Lanjutkan obat')
            ->assertSeeText('Evaluasi kondisi')
            ->assertSeeText('Kontrol ulang')
            ->assertSeeText('dr. Sehat')
            ->assertSee('data-control-tab="umum"', false)
            ->assertSee('id="bpjsControlPeriod"', false)
            ->assertSee('epasien/assets/css/surat-kontrol.css', false)
            ->assertSee('epasien/assets/js/surat-kontrol.js', false)
            ->assertDontSee('no_antrian');
    }

    public function test_bpjs_endpoint_uses_patient_data_and_returns_control_letters(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_peserta' => '0002035874204',
        ];

        $this->mock(SuratKontrolService::class, function (MockInterface $mock) use (
            $patient
        ): void {
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('bpjsLettersForPatient')
                ->once()
                ->with($patient, '2026-07', false)
                ->andReturn([
                    'available' => true,
                    'masked_card_number' => '•••••••••4204',
                    'meta_data' => ['code' => '200', 'message' => 'Sukses'],
                    'periode' => ['label' => 'Juni 2026 dan Juli 2026'],
                    'surat_kontrol' => [
                        [
                            'no_surat_kontrol' => '0126R0010726K000001',
                            'tgl_rencana_kontrol' => '2026-07-31',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($this->patientUser())
            ->getJson(route('suratKontrol.bpjs', [
                'periode' => '2026-07',
                'no_peserta' => '9999999999999',
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.masked_card_number', '•••••••••4204')
            ->assertJsonPath(
                'data.surat_kontrol.0.no_surat_kontrol',
                '0126R0010726K000001'
            );
    }

    public function test_bpjs_endpoint_can_search_months_automatically(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_peserta' => '0002035874204',
        ];
        $currentPeriod = now()->format('Y-m');

        $this->mock(SuratKontrolService::class, function (MockInterface $mock) use (
            $patient,
            $currentPeriod
        ): void {
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('bpjsLettersForPatient')
                ->once()
                ->with($patient, $currentPeriod, true)
                ->andReturn([
                    'available' => true,
                    'masked_card_number' => '•••••••••4204',
                    'search_mode' => 'automatic',
                    'meta_data' => ['code' => '204', 'message' => 'Data tidak ditemukan'],
                    'periode' => [
                        'label' => 'April 2026 sampai Oktober 2026',
                        'jumlah_bulan' => 7,
                    ],
                    'surat_kontrol' => [],
                ]);
        });

        $response = $this->actingAs($this->patientUser())
            ->getJson(route('suratKontrol.bpjs', ['mode' => 'automatic']));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'empty')
            ->assertJsonPath('data.search_mode', 'automatic')
            ->assertJsonPath('data.periode.jumlah_bulan', 7);
    }

    public function test_general_repository_does_not_select_queue_number(): void
    {
        $source = file_get_contents(app_path(
            'Repositories/epasien/menu/Surat/SuratKontrolRepository.php'
        ));

        $this->assertIsString($source);
        $this->assertStringContainsString("'skdp_bpjs.diagnosa'", $source);
        $this->assertStringContainsString("'skdp_bpjs.status'", $source);
        $this->assertStringNotContainsString("'skdp_bpjs.no_antrian'", $source);
    }

    public function test_control_letter_assets_include_smartphone_interactions(): void
    {
        $styles = file_get_contents(public_path(
            'epasien/assets/css/surat-kontrol.css'
        ));
        $scripts = file_get_contents(public_path(
            'epasien/assets/js/surat-kontrol.js'
        ));

        $this->assertIsString($styles);
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        $this->assertStringContainsString('(pointer: coarse)', $styles);
        $this->assertStringContainsString('position: sticky;', $styles);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $styles);
        $this->assertStringContainsString('touch-action: manipulation;', $styles);
        $this->assertIsString($scripts);
        $this->assertStringContainsString('data-bpjs-edit-search', $scripts);
        $this->assertStringContainsString('scrollIntoView', $scripts);
    }

    public function test_bpjs_service_disables_referral_fallback(): void
    {
        $repository = Mockery::mock(SuratKontrolRepository::class);
        $rencanaKontrol = Mockery::mock(RencanaKontrolService::class);
        $rencanaKontrol
            ->shouldReceive('listByCardNumber')
            ->once()
            ->with('2026-07-01', '0002035874204', 2, false)
            ->andReturn([
                'meta_data' => ['code' => '204', 'message' => 'Data tidak ditemukan'],
                'periode' => ['label' => 'Juni 2026 dan Juli 2026'],
                'surat_kontrol' => [],
            ]);

        $result = (new SuratKontrolService($repository, $rencanaKontrol))
            ->bpjsLettersForPatient((object) [
                'no_peserta' => '0002 0358 74204',
            ], '2026-07');

        $this->assertTrue($result['available']);
        $this->assertSame('•••••••••4204', $result['masked_card_number']);
        $this->assertSame('period', $result['search_mode']);
        $this->assertSame([], $result['surat_kontrol']);
    }

    public function test_bpjs_service_uses_seven_month_window_for_automatic_search(): void
    {
        $repository = Mockery::mock(SuratKontrolRepository::class);
        $rencanaKontrol = Mockery::mock(RencanaKontrolService::class);
        $rencanaKontrol
            ->shouldReceive('listByCardNumber')
            ->once()
            ->with('2026-07-01', '0002035874204', 2, false, 3, 3)
            ->andReturn([
                'meta_data' => ['code' => '204', 'message' => 'Data tidak ditemukan'],
                'periode' => [
                    'label' => 'April 2026 sampai Oktober 2026',
                    'jumlah_bulan' => 7,
                ],
                'surat_kontrol' => [],
            ]);

        $result = (new SuratKontrolService($repository, $rencanaKontrol))
            ->bpjsLettersForPatient((object) [
                'no_peserta' => '0002035874204',
            ], '2026-07', true);

        $this->assertSame('automatic', $result['search_mode']);
        $this->assertSame(7, $result['periode']['jumlah_bulan']);
    }

    private function patientUser(): User
    {
        $user = new User([
            'name' => 'Budi',
            'username' => '000123',
            'email' => 'budi@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect());

        return $user;
    }
}
