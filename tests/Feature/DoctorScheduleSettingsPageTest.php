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
            'leave_dates' => ['2026-10-20', '2026-10-27'],
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
            ->assertSeeText('Atur Libur')
            ->assertSee('type="date" name="leave_date"', false)
            ->assertSee('id="scheduleLeaveModal"', false)
            ->assertSee('id="openDoctorLeave"', false)
            ->assertSee('name="doctor_code" id="leaveDoctorCode"', false)
            ->assertSee('form="scheduleLeaveForm" id="leaveSaveButton"', false)
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSeeText('Daftar Dokter Libur')
            ->assertSee('class="schedule-leave-edit js-edit-doctor-leave"', false)
            ->assertSee('<option value="dryunus"', false)
            ->assertSee('value="2026-10-20"', false)
            ->assertSee('value="2026-10-27"', false)
            ->assertSeeText('20/10/2026')
            ->assertSeeText('Batalkan')
            ->assertSee(route('doctorScheduleSettings.updateLeave'), false)
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
                        'day' => 'MINGGU',
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
                'day' => 'MINGGU',
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

    public function test_invalid_leave_date_reopens_the_picker_and_preserves_the_selected_schedule(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 10, 12)->setTime(8, 0));
        $page = $this->pageData(new LengthAwarePaginator([[
            'doctor_code' => 'dryunus', 'doctor_name' => 'dr. Achmad Yunus, Sp.A',
            'clinic_code' => 'ANA', 'clinic_name' => 'Poliklinik Anak',
            'day' => 'SELASA', 'day_label' => 'Selasa', 'start_time' => '12:00', 'end_time' => '13:00',
            'time_label' => '12.00 - 13.00 WIB', 'quota' => 50, 'quota_label' => '50 pasien',
        ]], 1, 15));
        $this->mock(DoctorScheduleService::class, function (MockInterface $mock) use ($page): void {
            $mock->shouldNotReceive('setOnLeave');
            $mock->shouldReceive('emptyPage')->once()->andReturn($page);
            $mock->shouldReceive('page')->once()->andReturn($page);
        });

        $this->actingAs($this->authorizedStaff())
            ->from(route('doctorScheduleSettings.index'))
            ->patch(route('doctorScheduleSettings.updateLeave'), [
                'original_doctor_code' => 'dryunus', 'original_day' => 'SELASA', 'original_start_time' => '12:00',
                '_method' => 'PATCH', 'is_on_leave' => 1, 'leave_date' => '2026-10-06',
            ])->assertSessionHasErrors('leave_date')->assertSessionHasInput('leave_date', '2026-10-06');

        $this->get(route('doctorScheduleSettings.index'))->assertOk()
            ->assertSee('id="scheduleLeaveForm" class="schedule-leave-form"', false)
            ->assertSee('data-reopen="true"', false)
            ->assertSee('type="date" name="leave_date" value="2026-10-06"', false)
            ->assertSeeText('Tanggal libur tidak boleh sebelum hari ini.');
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
            'leaveSchedules' => $schedules->getCollection(),
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
