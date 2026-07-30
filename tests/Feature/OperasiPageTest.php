<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\OperasiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class OperasiPageTest extends TestCase
{
    public function test_guest_cannot_access_operation_routes(): void
    {
        $this->get(route('operasi.index'))
            ->assertRedirect(route('login'));
        $this->get(route('operasi.detail', [
            'no_rawat' => '2026/07/29/000001',
            'tanggal' => '2026-07-29',
            'jam_mulai' => '08:00:00',
        ]))->assertRedirect(route('login'));
    }

    public function test_page_shows_the_authenticated_patients_operations(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi Santoso',
        ];
        $operations = new LengthAwarePaginator([
            $this->formattedOperation(),
        ], 1, 8);

        $this->mock(OperasiService::class, function (MockInterface $mock) use (
            $patient,
            $operations
        ): void {
            $counts = [
                'all' => 4,
                'terjadwal' => 1,
                'proses' => 1,
                'menunggu_laporan' => 1,
                'selesai' => 1,
            ];
            $mock->shouldReceive('emptyCounts')->once()->andReturn($counts);
            $mock->shouldReceive('patientForUser')->once()->andReturn($patient);
            $mock->shouldReceive('operationsForUser')
                ->once()
                ->withArgs(
                    fn (
                        User $user,
                        ?string $workflowStatus,
                        ?string $careType,
                        ?string $startDate,
                        ?string $endDate,
                        ?string $search
                    ): bool => $user->username === '000123'
                        && $workflowStatus === 'selesai'
                        && $careType === 'Ranap'
                        && $startDate === '2026-07-01'
                        && $endDate === '2026-07-31'
                        && $search === 'appendektomi'
                )
                ->andReturn($operations);
            $mock->shouldReceive('countsForUser')->once()->andReturn($counts);
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('operasi.index', [
                'status' => 'selesai',
                'status_layanan' => 'Ranap',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-07-31',
                'q' => 'appendektomi',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.operasi.index')
            ->assertSeeText('Operasi')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('Appendektomi')
            ->assertSeeText('Laporan Tersedia')
            ->assertSeeText('Kamar Operasi 1')
            ->assertSeeText('Lihat Detail')
            ->assertSee('data-detail-url=', false)
            ->assertSee('id="operationDetailModal"', false)
            ->assertSee('class="laboratory-card operation-card tone-selesai"', false)
            ->assertSeeText('Reset semua filter');
    }

    public function test_detail_endpoint_returns_structured_operation_data(): void
    {
        $detail = [
            'booking' => [
                'no_rawat' => '2026/07/29/000001',
                'judul' => 'Appendektomi',
            ],
            'pelaksanaan' => [
                'tersedia' => true,
                'operator_utama' => 'dr. Operator',
            ],
            'laporan' => [
                'tersedia' => true,
                'diagnosa_postoperasi' => 'Apendisitis akut',
            ],
        ];

        $this->mock(OperasiService::class, function (MockInterface $mock) use ($detail): void {
            $mock->shouldReceive('detailForUser')
                ->once()
                ->withArgs(
                    fn (
                        User $user,
                        string $treatmentNumber,
                        string $bookingDate,
                        string $startTime
                    ): bool => $user->username === '000123'
                        && $treatmentNumber === '2026/07/29/000001'
                        && $bookingDate === '2026-07-29'
                        && $startTime === '08:00:00'
                )
                ->andReturn($detail);
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('operasi.detail', [
                'no_rawat' => '2026/07/29/000001',
                'tanggal' => '2026-07-29',
                'jam_mulai' => '08:00:00',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.booking.no_rawat',
                '2026/07/29/000001'
            )
            ->assertJsonPath(
                'data.pelaksanaan.operator_utama',
                'dr. Operator'
            )
            ->assertJsonPath(
                'data.laporan.diagnosa_postoperasi',
                'Apendisitis akut'
            );
    }

    public function test_detail_endpoint_hides_an_operation_not_owned_by_the_patient(): void
    {
        $this->mock(OperasiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('detailForUser')->once()->andReturnNull();
        });

        $response = $this->actingAs($this->patientUser())->getJson(
            route('operasi.detail', [
                'no_rawat' => '2026/07/29/999999',
                'tanggal' => '2026-07-29',
                'jam_mulai' => '08:00:00',
            ])
        );

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Data operasi tidak ditemukan.');
    }

    public function test_page_rejects_an_invalid_workflow_status(): void
    {
        $this->mock(OperasiService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptyCounts');
            $mock->shouldNotReceive('patientForUser');
            $mock->shouldNotReceive('operationsForUser');
            $mock->shouldNotReceive('countsForUser');
        });

        $response = $this->actingAs($this->patientUser())
            ->from(route('operasi.index'))
            ->get(route('operasi.index', [
                'status' => 'tidak-valid',
            ]));

        $response
            ->assertRedirect(route('operasi.index'))
            ->assertSessionHasErrors('status');
    }

    /**
     * @return array<string, mixed>
     */
    private function formattedOperation(): array
    {
        return [
            'no_rawat' => '2026/07/29/000001',
            'tanggal_booking' => '2026-07-29',
            'tanggal_booking_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_mulai' => '08:00',
            'jam_mulai_key' => '08:00:00',
            'jam_selesai' => '10:00',
            'durasi_jadwal' => '2 jam',
            'hari_short' => 'Rab',
            'tanggal_angka' => '29',
            'bulan_short' => 'Jul',
            'status' => 'selesai',
            'status_label' => 'Laporan Tersedia',
            'status_icon' => 'bi-check-circle',
            'status_booking' => 'Selesai',
            'status_layanan' => 'ranap',
            'jenis_layanan' => 'Rawat Inap',
            'layanan_tone' => 'ranap',
            'dokter_operator' => 'dr. Operator',
            'ruang_operasi' => 'Kamar Operasi 1',
            'poli' => 'Bedah',
            'tanggal_pelaksanaan' => '2026-07-29',
            'tanggal_pelaksanaan_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_pelaksanaan' => '08:10',
            'pelaksanaan_tercatat' => true,
            'tanggal_laporan' => '2026-07-29',
            'tanggal_laporan_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_laporan' => '08:15',
            'laporan_tersedia' => true,
            'jumlah_tindakan' => 1,
            'judul' => 'Appendektomi',
            'kategori' => 'Besar',
            'tindakan' => [[
                'kode' => 'OP001',
                'nama' => 'Appendektomi',
                'kategori' => 'Besar',
            ]],
        ];
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
