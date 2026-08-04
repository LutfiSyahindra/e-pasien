<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\RiwayatMcuService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\Concerns\AuthorizesEpasienMenuRoutes;
use Tests\TestCase;

class RiwayatMcuPageTest extends TestCase
{
    use AuthorizesEpasienMenuRoutes;

    public function test_guest_cannot_access_mcu_routes(): void
    {
        $this->get(route('riwayatMcu.index'))
            ->assertRedirect(route('login'));
        $this->get(route('riwayatMcu.detail', [
            'no_rawat' => '2026/07/24/000001',
        ]))->assertRedirect(route('login'));
    }

    public function test_patient_can_view_owned_mcu_history(): void
    {
        $patient = (object) [
            'no_rkm_medis' => '000123',
            'nm_pasien' => 'Budi Santoso',
        ];
        $assessments = new LengthAwarePaginator([[
            'no_rawat' => '2026/07/24/000001',
            'tanggal' => '2026-07-24',
            'tanggal_lengkap' => 'Jumat, 24 Juli 2026',
            'jam' => '09:35',
            'hari_short' => 'Jum',
            'tanggal_angka' => '24',
            'bulan_short' => 'Jul',
            'dokter' => 'dr. Sehat',
            'poli' => 'Medical Check Up',
            'keadaan' => 'Baik',
            'kesadaran' => 'Composmentis',
            'kesimpulan' => 'Sehat untuk bekerja.',
            'anjuran' => 'Olahraga rutin.',
            'vitals' => [
                'tekanan_darah' => '120/80 mmHg',
                'nadi' => '80 x/menit',
                'suhu' => '36.5 °C',
            ],
        ]], 1, 8);

        $this->mock(RiwayatMcuService::class, function (
            MockInterface $mock
        ) use ($patient, $assessments): void {
            $mock->shouldReceive('emptySummary')->once()->andReturn([
                'all' => 0,
                'current_year' => 0,
                'latest_at' => null,
                'latest_label' => '-',
            ]);
            $mock->shouldReceive('patientForUser')
                ->once()
                ->andReturn($patient);
            $mock->shouldReceive('assessmentsForUser')
                ->once()
                ->withArgs(
                    fn (
                        User $user,
                        ?string $startDate,
                        ?string $endDate,
                        ?string $doctorCode,
                        ?string $search
                    ): bool => $user->username === '000123'
                        && $startDate === '2026-07-01'
                        && $endDate === '2026-07-31'
                        && $doctorCode === 'D001'
                        && $search === 'sehat'
                )
                ->andReturn($assessments);
            $mock->shouldReceive('summaryForUser')->once()->andReturn([
                'all' => 3,
                'current_year' => 1,
                'latest_at' => '2026-07-24 09:35:00',
                'latest_label' => 'Jumat, 24 Juli 2026',
            ]);
            $mock->shouldReceive('doctorsForUser')
                ->once()
                ->andReturn(new Collection([[
                    'code' => 'D001',
                    'name' => 'dr. Sehat',
                ]]));
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('riwayatMcu.index', [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-07-31',
                'dokter' => 'D001',
                'q' => 'sehat',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.riwayatMcu.index')
            ->assertSeeText('Riwayat Medical Check Up')
            ->assertSeeText('Budi Santoso')
            ->assertSeeText('Medical Check Up')
            ->assertSeeText('Sehat untuk bekerja.')
            ->assertSeeText('Olahraga rutin.')
            ->assertSee('class="mcu-filter-panel"', false)
            ->assertSee('class="mcu-card-mobile-date"', false)
            ->assertSee('id="mcuDetailModal"', false)
            ->assertSee('data-detail-url=', false);
    }

    public function test_detail_endpoint_hides_mcu_owned_by_another_patient(): void
    {
        $this->mock(RiwayatMcuService::class, function (
            MockInterface $mock
        ): void {
            $mock->shouldReceive('detailForUser')
                ->once()
                ->andReturnNull();
        });

        $this->actingAs($this->patientUser())
            ->getJson(route('riwayatMcu.detail', [
                'no_rawat' => '2026/07/24/999999',
            ]))
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Penilaian MCU tidak ditemukan.'
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
