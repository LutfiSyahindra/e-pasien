<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\PermintaanTindakan\PemeriksaanLaboratService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\Concerns\AuthorizesEpasienMenuRoutes;
use Tests\TestCase;

class PemeriksaanLaboratPageTest extends TestCase
{
    use AuthorizesEpasienMenuRoutes;

    public function test_page_shows_the_authenticated_patients_laboratory_requests(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi Santoso',
        ];
        $requests = new LengthAwarePaginator([[
            'noorder' => 'PL20260729001',
            'no_rawat' => '2026/07/29/000001',
            'tanggal_permintaan' => '2026-07-29',
            'tanggal_permintaan_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_permintaan' => '08:15',
            'hari_short' => 'Rab',
            'tanggal_angka' => '29',
            'bulan_short' => 'Jul',
            'tanggal_sampel' => '2026-07-29',
            'tanggal_sampel_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_sampel' => '08:45',
            'tanggal_hasil' => '2026-07-29',
            'tanggal_hasil_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_hasil' => '10:00',
            'status' => 'selesai',
            'status_label' => 'Hasil Tersedia',
            'status_icon' => 'bi-check-circle',
            'status_layanan' => 'Ralan',
            'jenis_layanan' => 'Rawat Jalan',
            'layanan_tone' => 'ralan',
            'dokter_perujuk' => 'dr. Sehat',
            'poli' => 'Poli Umum',
            'diagnosa_klinis' => 'Demam',
            'informasi_tambahan' => 'Puasa 8 jam',
            'jumlah_pemeriksaan' => 1,
            'jumlah_hasil' => 3,
            'hasil_tersedia' => true,
            'pemeriksaan_diminta' => [[
                'kode' => 'LAB001',
                'nama' => 'Darah Lengkap',
            ]],
        ]], 1, 8);

        $this->mock(PemeriksaanLaboratService::class, function (MockInterface $mock) use (
            $patient,
            $requests
        ): void {
            $mock->shouldReceive('emptyCounts')->once()->andReturn([
                'all' => 4,
                'menunggu' => 1,
                'proses' => 1,
                'selesai' => 2,
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
                        && $careType === 'Ralan'
                        && $startDate === '2026-07-01'
                        && $endDate === '2026-07-31'
                        && $search === 'darah'
                )
                ->andReturn($requests);
            $mock->shouldReceive('countsForUser')->once()->andReturn([
                'all' => 4,
                'menunggu' => 1,
                'proses' => 1,
                'selesai' => 2,
            ]);
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('pemeriksaanLaborat.index', [
                'status_hasil' => 'selesai',
                'status_layanan' => 'Ralan',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-07-31',
                'q' => 'darah',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.PermintaanTindakan.pemeriksaanLaborat.pemeriksaanLaborat')
            ->assertSeeText('Pemeriksaan Laborat')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('PL20260729001')
            ->assertSeeText('Darah Lengkap')
            ->assertSeeText('Hasil Tersedia')
            ->assertSeeText('Puasa 8 jam')
            ->assertSeeText('Lihat Hasil')
            ->assertSee('data-result-url=', false)
            ->assertSee('id="laboratoryResultModal"', false)
            ->assertSee('data-mobile-filter-toggle', false)
            ->assertSee('id="laboratoryFilterPanel"', false)
            ->assertSeeText('5 aktif')
            ->assertSee('class="laboratory-card tone-selesai"', false)
            ->assertSee('name="status_layanan"', false)
            ->assertSee('value="2026-07-01"', false)
            ->assertSeeText('Reset semua filter');
    }

    public function test_result_endpoint_returns_structured_laboratory_results(): void
    {
        $result = [
            'permintaan' => [
                'noorder' => 'PL20260729001',
                'no_rawat' => '2026/07/29/000001',
            ],
            'ringkasan' => [
                'jumlah_jenis' => 1,
                'jumlah_parameter' => 2,
                'jumlah_catatan' => 1,
            ],
            'kelompok_hasil' => [[
                'kode' => 'LAB001',
                'nama' => 'Darah Lengkap',
                'jumlah_parameter' => 2,
                'parameter' => [[
                    'nama' => 'Hemoglobin',
                    'nilai' => '13.5',
                    'satuan' => 'g/dL',
                    'nilai_rujukan' => '12-16',
                    'keterangan' => '-',
                    'memiliki_catatan' => false,
                ]],
            ]],
        ];

        $this->mock(PemeriksaanLaboratService::class, function (MockInterface $mock) use ($result): void {
            $mock->shouldReceive('resultForUser')
                ->once()
                ->withArgs(
                    fn (User $user, string $orderNumber): bool => $user->username === '000123'
                        && $orderNumber === 'PL20260729001'
                )
                ->andReturn($result);
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('pemeriksaanLaborat.result', [
                'noorder' => 'PL20260729001',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.permintaan.noorder', 'PL20260729001')
            ->assertJsonPath('data.ringkasan.jumlah_parameter', 2)
            ->assertJsonPath(
                'data.kelompok_hasil.0.parameter.0.nama',
                'Hemoglobin'
            );
    }

    public function test_result_endpoint_hides_an_order_that_is_not_owned_by_the_patient(): void
    {
        $this->mock(PemeriksaanLaboratService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resultForUser')->once()->andReturnNull();
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('pemeriksaanLaborat.result', [
                'noorder' => 'PL-MILIK-PASIEN-LAIN',
            ])
        );

        $response
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Permintaan laboratorium tidak ditemukan.'
            );
    }

    public function test_page_rejects_an_invalid_result_status(): void
    {
        $this->mock(PemeriksaanLaboratService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptyCounts');
            $mock->shouldNotReceive('patientForUser');
            $mock->shouldNotReceive('requestsForUser');
            $mock->shouldNotReceive('countsForUser');
        });

        $response = $this->actingAs($this->patientUser())
            ->from(route('pemeriksaanLaborat.index'))
            ->get(route('pemeriksaanLaborat.index', [
                'status_hasil' => 'tidak-valid',
            ]));

        $response
            ->assertRedirect(route('pemeriksaanLaborat.index'))
            ->assertSessionHasErrors('status_hasil');
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
