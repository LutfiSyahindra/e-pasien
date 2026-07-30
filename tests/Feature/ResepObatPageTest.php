<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\ResepObatService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class ResepObatPageTest extends TestCase
{
    public function test_guest_cannot_access_prescription_page(): void
    {
        $this->get(route('resepObat.index'))
            ->assertRedirect(route('login'));
    }

    public function test_page_shows_the_authenticated_patients_prescriptions(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi Santoso',
        ];
        $prescriptions = new LengthAwarePaginator([
            $this->doctorPrescription(),
            $this->dischargePrescription(),
        ], 2, 8);

        $this->mock(ResepObatService::class, function (
            MockInterface $mock
        ) use ($patient, $prescriptions): void {
            $mock->shouldReceive('emptyCounts')->once()->andReturn([
                'all' => 2,
                'ralan' => 1,
                'ranap' => 1,
                'dokter' => 1,
                'pulang' => 1,
            ]);
            $mock->shouldReceive('patientForUser')
                ->once()
                ->andReturn($patient);
            $mock->shouldReceive('prescriptionsForUser')
                ->once()
                ->withArgs(
                    fn (
                        User $user,
                        ?string $prescriptionType,
                        ?string $careType,
                        ?string $startDate,
                        ?string $endDate,
                        ?string $search
                    ): bool => $user->username === '000123'
                        && $prescriptionType === 'dokter'
                        && $careType === 'ralan'
                        && $startDate === '2026-07-01'
                        && $endDate === '2026-07-31'
                        && $search === 'amoxicillin'
                )
                ->andReturn($prescriptions);
            $mock->shouldReceive('countsForUser')->once()->andReturn([
                'all' => 2,
                'ralan' => 1,
                'ranap' => 1,
                'dokter' => 1,
                'pulang' => 1,
            ]);
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('resepObat.index', [
                'jenis_resep' => 'dokter',
                'status_layanan' => 'ralan',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-07-31',
                'q' => 'amoxicillin',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.resepObat.index')
            ->assertSeeText('Resep Obat')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('RSP2026072901')
            ->assertSeeText('Amoxicillin 500 mg')
            ->assertSeeText('Puyer Batuk')
            ->assertSeeText('Sudah Diserahkan')
            ->assertSeeText('Resep Pulang')
            ->assertSeeText('Menunggu Validasi')
            ->assertSee('name="jenis_resep"', false)
            ->assertSee('name="status_layanan"', false)
            ->assertSee('class="prescription-details"', false)
            ->assertSeeText('Reset semua filter');
    }

    public function test_page_rejects_an_invalid_care_type(): void
    {
        $this->mock(ResepObatService::class, function (
            MockInterface $mock
        ): void {
            $mock->shouldNotReceive('emptyCounts');
            $mock->shouldNotReceive('patientForUser');
            $mock->shouldNotReceive('prescriptionsForUser');
            $mock->shouldNotReceive('countsForUser');
        });

        $response = $this->actingAs($this->patientUser())
            ->from(route('resepObat.index'))
            ->get(route('resepObat.index', [
                'status_layanan' => 'UGD',
            ]));

        $response
            ->assertRedirect(route('resepObat.index'))
            ->assertSessionHasErrors('status_layanan');
    }

    /**
     * @return array<string, mixed>
     */
    private function doctorPrescription(): array
    {
        return [
            'nomor_resep' => 'RSP2026072901',
            'no_rawat' => '2026/07/29/000001',
            'sumber' => 'dokter',
            'jenis_resep' => 'Resep Dokter',
            'jenis_resep_icon' => 'bi-prescription2',
            'tanggal' => '2026-07-29',
            'tanggal_lengkap' => 'Rabu, 29 Juli 2026',
            'jam' => '08:15',
            'hari_short' => 'Rab',
            'tanggal_angka' => '29',
            'bulan_short' => 'Jul',
            'tanggal_selesai' => '2026-07-29',
            'tanggal_selesai_lengkap' => 'Rabu, 29 Juli 2026',
            'jam_selesai' => '09:15',
            'status' => 'selesai',
            'status_label' => 'Sudah Diserahkan',
            'status_icon' => 'bi-check-circle-fill',
            'status_layanan' => 'ralan',
            'jenis_layanan' => 'Rawat Jalan',
            'layanan_tone' => 'ralan',
            'dokter' => 'dr. Sehat',
            'poli' => 'Poli Umum',
            'obat' => [[
                'kode' => 'OBT001',
                'nama' => 'Amoxicillin 500 mg',
                'jumlah' => '10',
                'satuan' => 'TAB',
                'aturan_pakai' => '3 x 1 sesudah makan',
            ]],
            'racikan' => [[
                'nomor' => '1',
                'nama' => 'Puyer Batuk',
                'metode' => 'Puyer',
                'jumlah' => '10',
                'aturan_pakai' => '3 x 1',
                'keterangan' => 'Sesudah makan',
            ]],
            'jumlah_obat' => 1,
            'jumlah_racikan' => 1,
            'jumlah_item' => 2,
            'selesai' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dischargePrescription(): array
    {
        return [
            'nomor_resep' => 'PRP2026072901',
            'no_rawat' => '2026/07/29/000002',
            'sumber' => 'pulang',
            'jenis_resep' => 'Resep Pulang',
            'jenis_resep_icon' => 'bi-house-heart',
            'tanggal' => '2026-07-29',
            'tanggal_lengkap' => 'Rabu, 29 Juli 2026',
            'jam' => '11:00',
            'hari_short' => 'Rab',
            'tanggal_angka' => '29',
            'bulan_short' => 'Jul',
            'tanggal_selesai' => null,
            'tanggal_selesai_lengkap' => '-',
            'jam_selesai' => '-',
            'status' => 'menunggu',
            'status_label' => 'Menunggu Validasi',
            'status_icon' => 'bi-clock-history',
            'status_layanan' => 'ranap',
            'jenis_layanan' => 'Rawat Inap',
            'layanan_tone' => 'ranap',
            'dokter' => 'dr. Rawat',
            'poli' => 'Rawat Inap',
            'obat' => [],
            'racikan' => [],
            'jumlah_obat' => 0,
            'jumlah_racikan' => 0,
            'jumlah_item' => 0,
            'selesai' => false,
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
