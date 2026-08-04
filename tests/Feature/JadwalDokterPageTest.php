<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\menu\JadwalDokterService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\Concerns\AuthorizesEpasienMenuRoutes;
use Tests\TestCase;

class JadwalDokterPageTest extends TestCase
{
    use AuthorizesEpasienMenuRoutes;

    public function test_guest_cannot_access_doctor_schedule_page(): void
    {
        $this->get(route('jadwalDokter.index'))
            ->assertRedirect(route('login'));
    }

    public function test_patient_can_filter_mobile_friendly_doctor_schedule(): void
    {
        $emptyPage = $this->pageData(new LengthAwarePaginator([], 0, 12));
        $page = $this->pageData(new LengthAwarePaginator([[
            'doctor_code' => 'dryunus',
            'doctor_name' => 'dr. Achmad Yunus, Sp.A',
            'doctor_initials' => 'AY',
            'gender_icon' => 'bi-person',
            'clinic_code' => 'ANA',
            'clinic_name' => 'Poliklinik Anak',
            'day' => 'SELASA',
            'day_label' => 'Selasa',
            'time_start' => '12.00',
            'time_end' => '13.00',
            'time_label' => '12.00 – 13.00 WIB',
            'quota' => 50,
            'quota_label' => '50 pasien',
            'is_today' => false,
        ]], 1, 12));

        $this->mock(JadwalDokterService::class, function (MockInterface $mock) use (
            $emptyPage,
            $page
        ): void {
            $mock->shouldReceive('emptyPage')
                ->once()
                ->with('anak', 'SELASA', 'ANA')
                ->andReturn($emptyPage);
            $mock->shouldReceive('page')
                ->once()
                ->with('anak', 'SELASA', 'ANA')
                ->andReturn($page);
        });

        $response = $this->actingAs($this->patientUser())->get(
            route('jadwalDokter.index', [
                'q' => 'anak',
                'hari' => 'SELASA',
                'poli' => 'ANA',
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('e-pasien.menu.jadwalDokter.index')
            ->assertSeeText('Jadwal Dokter')
            ->assertSeeText('Temukan Dokter yang Tepat')
            ->assertSeeText('dr. Achmad Yunus, Sp.A')
            ->assertSeeText('Poliklinik Anak')
            ->assertSeeText('12.00 – 13.00 WIB')
            ->assertSeeText('50 pasien')
            ->assertSeeText('Daftar secara online')
            ->assertSee('class="doctor-schedule-days"', false)
            ->assertSee('class="doctor-schedule-grid"', false)
            ->assertSee('name="q"', false)
            ->assertSee('name="poli"', false)
            ->assertSee('jadwal-dokter.css');
    }

    public function test_doctor_schedule_page_rejects_invalid_day(): void
    {
        $this->mock(JadwalDokterService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('emptyPage');
            $mock->shouldNotReceive('page');
        });

        $this->actingAs($this->patientUser())
            ->from(route('jadwalDokter.index'))
            ->get(route('jadwalDokter.index', ['hari' => 'LIBUR']))
            ->assertRedirect(route('jadwalDokter.index'))
            ->assertSessionHasErrors('hari');
    }

    public function test_patient_can_filter_sunday_using_minggu_code(): void
    {
        $page = $this->pageData(new LengthAwarePaginator([], 0, 12));

        $this->mock(JadwalDokterService::class, function (MockInterface $mock) use ($page): void {
            $mock->shouldReceive('emptyPage')
                ->once()
                ->with('', 'MINGGU', '')
                ->andReturn($page);
            $mock->shouldReceive('page')
                ->once()
                ->with('', 'MINGGU', '')
                ->andReturn($page);
        });

        $this->actingAs($this->patientUser())
            ->get(route('jadwalDokter.index', ['hari' => 'MINGGU']))
            ->assertOk();
    }

    private function pageData(LengthAwarePaginator $schedules): array
    {
        return [
            'search' => 'anak',
            'day' => 'SELASA',
            'day_label' => 'Selasa',
            'clinic_code' => 'ANA',
            'days' => [
                'SEMUA' => ['label' => 'Semua hari', 'short' => 'Semua'],
                'SENIN' => ['label' => 'Senin', 'short' => 'Sen'],
                'SELASA' => ['label' => 'Selasa', 'short' => 'Sel'],
            ],
            'schedules' => $schedules,
            'clinics' => [[
                'code' => 'ANA',
                'name' => 'Poliklinik Anak',
            ]],
            'summary' => [
                'schedules' => 49,
                'doctors' => 18,
                'clinics' => 10,
            ],
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
