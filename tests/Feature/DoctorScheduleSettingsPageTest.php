<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\epasien\settings\DoctorScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorScheduleSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_doctor_schedule_settings(): void
    {
        $this->get(route('doctorScheduleSettings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_staff_can_view_and_filter_schedules(): void
    {
        $page = $this->pageData(new LengthAwarePaginator([[
            'doctor_code' => 'dryunus',
            'doctor_name' => 'dr. Achmad Yunus, Sp.A',
            'clinic_code' => 'ANA',
            'clinic_name' => 'Poliklinik Anak',
            'day' => 'SELASA',
            'day_label' => 'Selasa',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'time_label' => '12.00 – 13.00 WIB',
            'quota' => 50,
            'quota_label' => '50 pasien',
        ]], 1, 15));

        $this->mock(DoctorScheduleService::class, function (MockInterface $mock) use ($page): void {
            $mock->shouldReceive('emptyPage')
                ->once()
                ->with('anak', 'SELASA', 'ANA')
                ->andReturn($page);
            $mock->shouldReceive('page')
                ->once()
                ->with('anak', 'SELASA', 'ANA')
                ->andReturn($page);
        });

        $staff = $this->authorizedStaff();
        $this->assertTrue($staff->hasRole(config('access-control.super_admin_role')));

        $this->actingAs($staff)
            ->get(route('doctorScheduleSettings.index', [
                'q' => 'anak',
                'hari' => 'SELASA',
                'poli' => 'ANA',
            ]))
            ->assertOk()
            ->assertViewIs('e-pasien.settings.doctorSchedules.index')
            ->assertSeeText('Pengaturan Jadwal Dokter')
            ->assertSeeText('dr. Achmad Yunus, Sp.A')
            ->assertSeeText('Poliklinik Anak')
            ->assertSeeText('12.00 – 13.00 WIB')
            ->assertSee('data-doctor-code="dryunus"', false)
            ->assertSee('doctor-schedule-settings.css');
    }

    public function test_authorized_staff_can_update_a_schedule(): void
    {
        $this->mock(DoctorScheduleService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('update')
                ->once()
                ->with(
                    [
                        'doctor_code' => 'dryunus',
                        'day' => 'SELASA',
                        'start_time' => '12:00',
                    ],
                    [
                        'day' => 'RABU',
                        'start_time' => '13:00',
                        'end_time' => '14:30',
                        'quota' => 35,
                    ]
                );
        });

        $this->actingAs($this->authorizedStaff())
            ->put(route('doctorScheduleSettings.update'), [
                'original_doctor_code' => 'dryunus',
                'original_day' => 'SELASA',
                'original_start_time' => '12:00',
                'day' => 'RABU',
                'start_time' => '13:00',
                'end_time' => '14:30',
                'quota' => 35,
                'filter_q' => 'anak',
                'filter_day' => 'SEMUA',
                'filter_clinic' => 'ANA',
                'filter_page' => 2,
            ])
            ->assertRedirect(route('doctorScheduleSettings.index', [
                'q' => 'anak',
                'hari' => 'SEMUA',
                'poli' => 'ANA',
                'page' => 2,
            ]))
            ->assertSessionHas(
                'status',
                'Jadwal dokter berhasil diperbarui dan langsung tersinkron ke menu Jadwal Dokter.'
            );
    }

    public function test_schedule_update_rejects_an_invalid_time_range(): void
    {
        $this->mock(DoctorScheduleService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('update');
        });

        $this->actingAs($this->authorizedStaff())
            ->from(route('doctorScheduleSettings.index'))
            ->put(route('doctorScheduleSettings.update'), [
                'original_doctor_code' => 'dryunus',
                'original_day' => 'SELASA',
                'original_start_time' => '12:00',
                'day' => 'SELASA',
                'start_time' => '13:00',
                'end_time' => '12:30',
                'quota' => 35,
            ])
            ->assertRedirect(route('doctorScheduleSettings.index'))
            ->assertSessionHasErrors('end_time');
    }

    public function test_schedule_update_handles_a_hospital_database_error(): void
    {
        $this->mock(DoctorScheduleService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('update')
                ->once()
                ->andThrow(new RuntimeException('Connection refused'));
        });

        $this->actingAs($this->authorizedStaff())
            ->from(route('doctorScheduleSettings.index'))
            ->put(route('doctorScheduleSettings.update'), [
                'original_doctor_code' => 'dryunus',
                'original_day' => 'SELASA',
                'original_start_time' => '12:00',
                'day' => 'RABU',
                'start_time' => '13:00',
                'end_time' => '14:30',
                'quota' => 35,
            ])
            ->assertRedirect(route('doctorScheduleSettings.index'))
            ->assertSessionHasErrors([
                'schedule' => 'Jadwal belum dapat disimpan. '
                    .'Koneksi data rumah sakit sedang tidak tersedia.',
            ])
            ->assertSessionHasInput('day', 'RABU');
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(LengthAwarePaginator $schedules): array
    {
        return [
            'search' => 'anak',
            'day' => 'SELASA',
            'clinic_code' => 'ANA',
            'days' => [
                'SENIN' => 'Senin',
                'SELASA' => 'Selasa',
                'RABU' => 'Rabu',
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

    private function authorizedStaff(): User
    {
        $user = new User([
            'name' => 'Petugas Jadwal',
            'username' => 'petugas-jadwal',
            'email' => 'jadwal@example.test',
            'status' => true,
        ]);
        $user->setRelation('roles', collect([
            new Role([
                'name' => config('access-control.super_admin_role'),
                'guard_name' => 'web',
            ]),
        ]));

        return $user;
    }
}
