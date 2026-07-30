<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\RiwayatPemeriksaanService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class RiwayatPemeriksaanPageTest extends TestCase
{
    public function test_page_shows_completed_examinations_as_mobile_friendly_cards(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi Santoso',
        ];
        $examinations = new LengthAwarePaginator([[
            'no_reg' => '007',
            'no_rawat' => '2026/07/20/000007',
            'tanggal' => '2026-07-20',
            'tanggal_lengkap' => 'Senin, 20 Juli 2026',
            'hari_short' => 'Sen',
            'tanggal_angka' => '20',
            'bulan_short' => 'Jul',
            'jam' => '08:15',
            'status_lanjut' => 'Ralan',
            'jenis_layanan' => 'Rawat Jalan',
            'layanan_tone' => 'ralan',
            'dokter' => 'dr. Sehat',
            'kd_dokter' => 'D001',
            'poli' => 'Poli Umum',
            'kd_poli' => 'U001',
            'penjamin' => 'Umum',
            'kd_pj' => 'UMU',
            'status_bayar' => 'Sudah Bayar',
            'status_daftar' => 'Lama',
        ]], 1, 8);

        $this->mock(RiwayatPemeriksaanService::class, function (MockInterface $mock) use (
            $patient,
            $examinations
        ): void {
            $mock->shouldReceive('emptyCounts')->once()->andReturn([
                'all' => 3,
                'Ralan' => 2,
                'Ranap' => 1,
            ]);
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('completedHistory')->once()->withArgs(
                fn (
                    User $user,
                    ?string $careType,
                    ?string $startDate,
                    ?string $endDate,
                    ?string $doctorCode
                ): bool => $user->username === '000123'
                    && $careType === 'Ralan'
                    && $startDate === '2026-07-01'
                    && $endDate === '2026-07-31'
                    && $doctorCode === 'D001'
            )->andReturn($examinations);
            $mock->shouldReceive('completedCounts')->once()->andReturn([
                'all' => 3,
                'Ralan' => 2,
                'Ranap' => 1,
            ]);
            $mock->shouldReceive('completedDoctors')->once()->andReturn([
                ['code' => 'D001', 'name' => 'dr. Sehat'],
                ['code' => 'D002', 'name' => 'dr. Bugar'],
            ]);
        });

        $response = $this->actingAs($this->patientUser())
            ->get(route('riwayatPemeriksaan.index', [
                'status_lanjut' => 'Ralan',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-07-31',
                'dokter' => 'D001',
            ]));

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.riwayatPemeriksaan.riwayatPemeriksaan')
            ->assertSeeText('Riwayat Pemeriksaan')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('Rawat Jalan')
            ->assertSeeText('Poli Umum')
            ->assertSeeText('dr. Sehat')
            ->assertSeeText('2026/07/20/000007')
            ->assertSeeText('Pembayaran')
            ->assertSeeText('Lihat Resume')
            ->assertSee('data-payment-url=', false)
            ->assertSee('data-resume-url=', false)
            ->assertSee('id="examinationPaymentModal"', false)
            ->assertSee('id="examinationResumeModal"', false)
            ->assertSee('modal-fullscreen-md-down', false)
            ->assertSeeText('Perhitungan')
            ->assertDontSeeText('Cara menghitung total tagihan')
            ->assertSee('type: "computed-subtotal"', false)
            ->assertSeeText('Total bagian')
            ->assertDontSee('<th scope="col">No.</th>', false)
            ->assertSee('document.addEventListener("DOMContentLoaded"', false)
            ->assertSee('id="examinationMobileFilterToggle"', false)
            ->assertSee('aria-controls="examinationFilterPanel"', false)
            ->assertSee('id="examinationFilterPanel"', false)
            ->assertSeeText('4 filter sedang aktif')
            ->assertSee('class="examination-card tone-ralan"', false)
            ->assertSee('status_lanjut=Ranap', false)
            ->assertSee('name="tanggal_mulai"', false)
            ->assertSee('value="2026-07-01"', false)
            ->assertSee('name="tanggal_selesai"', false)
            ->assertSee('value="2026-07-31"', false)
            ->assertSee('name="dokter"', false)
            ->assertSeeText('dr. Bugar')
            ->assertSeeText('Reset semua filter');
    }

    public function test_resume_endpoint_returns_the_authenticated_patients_resume(): void
    {
        $resume = [
            'no_rawat' => '2026/07/20/000007',
            'status_lanjut' => 'Ralan',
            'jenis_layanan' => 'Rawat Jalan',
            'dokter' => 'dr. Sehat',
            'sections' => [[
                'title' => 'Diagnosis',
                'icon' => 'bi-file-medical',
                'items' => [[
                    'label' => 'Diagnosis Utama',
                    'value' => 'Infeksi saluran napas akut',
                    'code' => 'J06.9',
                ]],
            ]],
        ];

        $this->mock(RiwayatPemeriksaanService::class, function (MockInterface $mock) use ($resume): void {
            $mock->shouldReceive('resumeForUser')
                ->once()
                ->withArgs(
                    fn (User $user, string $noRawat, string $careType): bool => $user->username === '000123'
                        && $noRawat === '2026/07/20/000007'
                        && $careType === 'Ralan'
                )
                ->andReturn($resume);
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('riwayatPemeriksaan.resume', [
                'no_rawat' => '2026/07/20/000007',
                'status_lanjut' => 'Ralan',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.no_rawat', '2026/07/20/000007')
            ->assertJsonPath('data.jenis_layanan', 'Rawat Jalan')
            ->assertJsonPath('data.sections.0.items.0.code', 'J06.9');
    }

    public function test_resume_endpoint_returns_not_found_when_resume_is_unavailable(): void
    {
        $this->mock(RiwayatPemeriksaanService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resumeForUser')
                ->once()
                ->andReturnNull();
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('riwayatPemeriksaan.resume', [
                'no_rawat' => '2026/07/21/000008',
                'status_lanjut' => 'Ranap',
            ])
        );

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Resume pemeriksaan belum tersedia.');
    }

    public function test_payment_endpoint_returns_all_billing_rows_for_the_authenticated_patient(): void
    {
        $payment = [
            'no_rawat' => '2026/07/20/000007',
            'nomor_nota' => '2026/07/20/RJ0007',
            'status_bayar' => 'Sudah Bayar',
            'summary' => [
                'subtotal' => 25000,
                'tambahan' => 0,
                'pengurang' => 0,
                'total' => 25000,
                'jumlah_item' => 1,
                'jumlah_baris' => 2,
            ],
            'rows' => [
                [
                    'noindex' => '0',
                    'no_rawat' => '2026/07/20/000007',
                    'no' => 'No.Nota',
                    'nm_perawatan' => ': 2026/07/20/RJ0007',
                    'type' => 'information',
                ],
                [
                    'noindex' => '1',
                    'no_rawat' => '2026/07/20/000007',
                    'no' => '',
                    'nm_perawatan' => 'Jasa Periksa',
                    'type' => 'detail',
                    'totalbiaya' => 25000,
                ],
            ],
        ];

        $this->mock(RiwayatPemeriksaanService::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('paymentForUser')
                ->once()
                ->withArgs(
                    fn (User $user, string $noRawat): bool => $user->username === '000123'
                        && $noRawat === '2026/07/20/000007'
                )
                ->andReturn($payment);
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('riwayatPemeriksaan.payment', [
                'no_rawat' => '2026/07/20/000007',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.no_rawat', '2026/07/20/000007')
            ->assertJsonPath('data.nomor_nota', '2026/07/20/RJ0007')
            ->assertJsonPath('data.summary.total', 25000)
            ->assertJsonCount(2, 'data.rows');
    }

    public function test_payment_endpoint_hides_a_visit_that_does_not_belong_to_the_patient(): void
    {
        $this->mock(RiwayatPemeriksaanService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('paymentForUser')
                ->once()
                ->andReturnNull();
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('riwayatPemeriksaan.payment', [
                'no_rawat' => '2026/07/21/999999',
            ])
        );

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Nota pembayaran tidak ditemukan untuk kunjungan ini.');
    }

    public function test_page_rejects_an_unknown_care_type_filter(): void
    {
        $this->mock(RiwayatPemeriksaanService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptyCounts');
            $mock->shouldNotReceive('patientForUser');
            $mock->shouldNotReceive('completedHistory');
            $mock->shouldNotReceive('completedCounts');
            $mock->shouldNotReceive('completedDoctors');
        });

        $response = $this->actingAs($this->patientUser())
            ->from(route('riwayatPemeriksaan.index'))
            ->get(route('riwayatPemeriksaan.index', ['status_lanjut' => 'Lainnya']));

        $response
            ->assertRedirect(route('riwayatPemeriksaan.index'))
            ->assertSessionHasErrors('status_lanjut');
    }

    public function test_page_rejects_an_end_date_before_the_start_date(): void
    {
        $this->mock(RiwayatPemeriksaanService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptyCounts');
            $mock->shouldNotReceive('patientForUser');
            $mock->shouldNotReceive('completedHistory');
            $mock->shouldNotReceive('completedCounts');
            $mock->shouldNotReceive('completedDoctors');
        });

        $response = $this->actingAs($this->patientUser())
            ->from(route('riwayatPemeriksaan.index'))
            ->get(route('riwayatPemeriksaan.index', [
                'tanggal_mulai' => '2026-07-20',
                'tanggal_selesai' => '2026-07-19',
            ]));

        $response
            ->assertRedirect(route('riwayatPemeriksaan.index'))
            ->assertSessionHasErrors('tanggal_selesai');
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
