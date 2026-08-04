<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\Surat\SuratRujukanService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\AuthorizesEpasienMenuRoutes;
use Tests\TestCase;

class SuratRujukanPageTest extends TestCase
{
    use AuthorizesEpasienMenuRoutes;

    public function test_page_shows_mobile_referral_experience_and_general_data(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi',
            'no_peserta' => '0002035020396',
        ];
        $referrals = new LengthAwarePaginator([[
            'id' => 'general-1',
            'no_rujukan' => 'RUJ-001',
            'no_rawat' => '2026/07/01/000001',
            'tujuan' => 'RS Tujuan Sehat',
            'tanggal' => [
                'raw' => '2026-07-20',
                'iso_date' => '2026-07-20',
                'date_label' => '20 Juli 2026',
                'time_label' => '10:00 WIB',
            ],
            'diagnosa' => 'Diagnosis umum',
            'kode_dokter' => 'D001',
            'nama_dokter' => 'dr. Sehat',
            'kategori' => 'Non Bedah',
            'ambulans' => 'SENDIRI',
            'keterangan' => 'Kontrol lanjutan',
            'terapi' => 'Terapi lanjutan',
            'indikasi' => 'Perlu pemeriksaan',
            'kode_penjamin' => 'A09',
            'penjamin' => 'Asuransi Umum',
        ]], 1, 8, 1);

        $this->mock(
            SuratRujukanService::class,
            function (MockInterface $mock) use ($patient, $referrals): void {
                $mock->shouldReceive('patientForUser')
                    ->once()
                    ->andReturn($patient);
                $mock->shouldReceive('generalOutgoingForUser')
                    ->once()
                    ->with(
                        Mockery::type(User::class),
                        null,
                        null
                    )
                    ->andReturn($referrals);
            }
        );

        $response = $this->actingAs($this->patientUser())
            ->get(route('suratRujukan.index'));

        $response
            ->assertOk()
            ->assertSeeText('Surat Rujukan')
            ->assertSeeText('Rujukan BPJS')
            ->assertSeeText('Rujukan Keluar')
            ->assertSeeText('PCare')
            ->assertSeeText('Rumah Sakit')
            ->assertSeeText('Rujukan keluar umum')
            ->assertSeeText('dokumen tersimpan')
            ->assertSeeText('Penjamin selain BPJ')
            ->assertSeeText('RS Tujuan Sehat')
            ->assertSeeText('Diagnosis umum')
            ->assertSee('data-referral-tab="masuk"', false)
            ->assertSee('data-outgoing-tab="bpjs"', false)
            ->assertSee('epasien/assets/css/surat-rujukan.css', false)
            ->assertSee('epasien/assets/js/surat-rujukan.js', false);
    }

    public function test_incoming_endpoint_returns_both_bpjs_sources(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'no_peserta' => '0002035020396',
        ];

        $this->mock(
            SuratRujukanService::class,
            function (MockInterface $mock) use ($patient): void {
                $mock->shouldReceive('patientForUser')
                    ->once()
                    ->andReturn($patient);
                $mock->shouldReceive('incomingBpjsForPatient')
                    ->once()
                    ->with($patient)
                    ->andReturn([
                        'available' => true,
                        'masked_card_number' => '•••••••••0396',
                        'partial' => false,
                        'meta_data' => [
                            'code' => '200',
                            'message' => 'Sukses',
                        ],
                        'sources' => [
                            'pcare' => [
                                'label' => 'PCare',
                                'state' => 'success',
                                'count' => 1,
                            ],
                            'rumah_sakit' => [
                                'label' => 'Rumah Sakit',
                                'state' => 'success',
                                'count' => 1,
                            ],
                        ],
                        'rujukan' => [
                            ['no_rujukan' => 'PC-001', 'source' => 'pcare'],
                            ['no_rujukan' => 'RS-001', 'source' => 'rumah_sakit'],
                        ],
                    ]);
            }
        );

        $response = $this->actingAs($this->patientUser())
            ->getJson(route('suratRujukan.bpjs.masuk'));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data.rujukan')
            ->assertJsonPath('data.sources.pcare.state', 'success')
            ->assertJsonPath('data.sources.rumah_sakit.state', 'success');
    }

    public function test_outgoing_bpjs_endpoint_uses_validated_date_range(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'no_peserta' => '0002035020396',
        ];

        $this->mock(
            SuratRujukanService::class,
            function (MockInterface $mock) use ($patient): void {
                $mock->shouldReceive('patientForUser')
                    ->once()
                    ->andReturn($patient);
                $mock->shouldReceive('bpjsOutgoingForPatient')
                    ->once()
                    ->with($patient, '2026-07-01', '2026-07-31')
                    ->andReturn([
                        'available' => true,
                        'masked_card_number' => '•••••••••0396',
                        'meta_data' => [
                            'code' => '200',
                            'message' => 'Sukses',
                        ],
                        'rujukan' => [[
                            'no_rujukan' => '1828R0010726B000001',
                        ]],
                    ]);
            }
        );

        $response = $this->actingAs($this->patientUser())
            ->getJson(route('suratRujukan.bpjs.keluar', [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_akhir' => '2026-07-31',
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath(
                'data.rujukan.0.no_rujukan',
                '1828R0010726B000001'
            );
    }

    public function test_outgoing_bpjs_endpoint_rejects_reversed_date_range(): void
    {
        $this->mock(
            SuratRujukanService::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive('patientForUser');
                $mock->shouldNotReceive('bpjsOutgoingForPatient');
            }
        );

        $response = $this->actingAs($this->patientUser())
            ->getJson(route('suratRujukan.bpjs.keluar', [
                'tanggal_mulai' => '2026-07-31',
                'tanggal_akhir' => '2026-07-01',
            ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tanggal_mulai',
                'tanggal_akhir',
            ]);
    }

    public function test_general_repository_filters_patient_and_non_bpj_data(): void
    {
        $source = file_get_contents(app_path(
            'Repositories/epasien/menu/Surat/SuratRujukanRepository.php'
        ));

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'reg_periksa.no_rkm_medis'",
            $source
        );
        $this->assertStringContainsString(
            'UPPER(TRIM(reg_periksa.kd_pj)) <> ?',
            $source
        );
        $this->assertStringContainsString("['BPJ']", $source);
    }

    public function test_referral_assets_include_smartphone_interactions(): void
    {
        $styles = file_get_contents(public_path(
            'epasien/assets/css/surat-rujukan.css'
        ));
        $scripts = file_get_contents(public_path(
            'epasien/assets/js/surat-rujukan.js'
        ));

        $this->assertIsString($styles);
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        $this->assertStringContainsString('@media (pointer: coarse)', $styles);
        $this->assertStringContainsString('position: sticky;', $styles);
        $this->assertStringContainsString(
            'env(safe-area-inset-bottom)',
            $styles
        );
        $this->assertIsString($scripts);
        $this->assertStringContainsString('data-referral-retry', $scripts);
        $this->assertStringContainsString('AbortController', $scripts);
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
