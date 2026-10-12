<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\epasien\menu\JadwalDokterRepository;
use App\Repositories\epasien\settings\DoctorPhotoRepository;
use App\Repositories\epasien\settings\DoctorScheduleLeaveRepository;
use App\Services\epasien\menu\JadwalDokterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorScheduleLeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 10, 11)->setTime(8, 0));

        config(['database.connections.mysql_khanza' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('mysql_khanza');
        DB::connection('mysql_khanza')->getSchemaBuilder()->create('jadwal_spesialis', function (Blueprint $table): void {
            $table->string('kd_dokter');
            $table->string('kd_poli');
            $table->string('hari_kerja');
            $table->string('jam_mulai');
            $table->string('jam_selesai');
            $table->integer('kuota');
        });
        DB::connection('mysql_khanza')->table('jadwal_spesialis')->insert([
            'kd_dokter' => 'D001', 'kd_poli' => 'ANA', 'hari_kerja' => 'AKHAD',
            'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'kuota' => 35,
        ]);
    }

    public function test_staff_can_set_and_cancel_a_leave_date_without_changing_practice_details(): void
    {
        $this->actingAs($this->staff());
        $filters = ['filter_q' => 'anak', 'filter_day' => 'MINGGU', 'filter_clinic' => 'ANA', 'filter_page' => 2];
        $target = route('doctorScheduleSettings.index', ['q' => 'anak', 'hari' => 'MINGGU', 'poli' => 'ANA', 'page' => 2]);

        foreach ([1, 1, 0] as $value) {
            $this->patch(route('doctorScheduleSettings.updateLeave'), [
                ...$this->selection(), ...$filters, 'is_on_leave' => $value,
            ])->assertRedirect($target)->assertSessionHasNoErrors()->assertSessionHas('status');

            $this->assertDatabaseCount('doctor_schedule_leaves', $value);
            $this->assertDatabaseHas('jadwal_spesialis', [
                'kd_dokter' => 'D001', 'hari_kerja' => 'AKHAD',
                'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'kuota' => 35,
            ], 'mysql_khanza');
        }
    }

    public function test_leave_follows_schedule_when_staff_changes_day_and_start_time(): void
    {
        $this->actingAs($this->staff())
            ->patch(route('doctorScheduleSettings.updateLeave'), [...$this->selection(), 'is_on_leave' => 1])
            ->assertSessionHasNoErrors();

        $this->put(route('doctorScheduleSettings.update'), [
            ...$this->selection(), 'day' => 'SENIN',
            'start_time' => '09:00', 'end_time' => '11:00', 'quota' => 40,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('doctor_schedule_leaves', [
            'doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'SENIN', 'start_time' => '09:00',
            'leave_date' => '2026-10-11',
        ]);
        $this->assertDatabaseMissing('doctor_schedule_leaves', ['day' => 'MINGGU']);
    }

    public function test_missing_schedule_and_invalid_leave_state_are_rejected(): void
    {
        $this->actingAs($this->staff());
        $this->patch(route('doctorScheduleSettings.updateLeave'), [...$this->selection(), 'is_on_leave' => 'invalid'])
            ->assertSessionHasErrors('is_on_leave');
        $this->patch(route('doctorScheduleSettings.updateLeave'), [
            ...$this->selection(), 'original_start_time' => '16:00', 'is_on_leave' => 1,
        ])->assertSessionHasErrors('schedule');
        $this->assertDatabaseCount('doctor_schedule_leaves', 0);
    }

    public function test_guest_and_patient_cannot_toggle_doctor_leave(): void
    {
        $payload = [...$this->selection(), 'is_on_leave' => 1];
        $this->patch(route('doctorScheduleSettings.updateLeave'), $payload)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->patch(route('doctorScheduleSettings.updateLeave'), $payload)->assertForbidden();
        $this->assertDatabaseCount('doctor_schedule_leaves', 0);
    }

    public function test_leave_is_refreshed_after_cached_patient_schedules_and_only_matches_selected_date_and_slot(): void
    {
        Cache::flush();
        $schedule = (object) [
            'kd_dokter' => 'D001', 'nm_dokter' => 'dr. Budi', 'kd_poli' => 'ANA',
            'nm_poli' => 'Poliklinik Anak', 'hari_kerja' => 'AKHAD',
            'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'kuota' => 35,
        ];
        $repository = $this->createMock(JadwalDokterRepository::class);
        $repository->expects($this->once())->method('paginateSchedules')->willReturn(new LengthAwarePaginator([
            $schedule, (object) [...(array) $schedule, 'jam_mulai' => '13:00:00'],
            (object) [...(array) $schedule, 'hari_kerja' => 'SENIN'],
            (object) [...(array) $schedule, 'kd_dokter' => 'D002'],
            (object) [...(array) $schedule, 'kd_poli' => 'INT'],
        ], 5, 12));
        $repository->method('clinics')->willReturn(collect());
        $photos = $this->createMock(DoctorPhotoRepository::class);
        $photos->method('urlsForCodes')->willReturn([]);
        $service = new JadwalDokterService($repository, $photos);
        $leaves = app(DoctorScheduleLeaveRepository::class);

        $this->assertFalse($service->page(null, 'SEMUA', null)['schedules'][0]['is_on_leave']);
        $leaves->setOnLeave($schedule, true, '2026-10-18');
        $this->assertFalse($service->page(null, 'SEMUA', null)['schedules'][0]['is_on_leave']);
        $leaves->setOnLeave($schedule, true, '2026-10-11');
        $page = $service->page(null, 'SEMUA', null);
        $this->assertSame([true, false, false, false, false], $page['schedules']->pluck('is_on_leave')->all());
        $this->assertSame(['2026-10-11', '2026-10-18'], $page['schedules'][0]['leave_dates']);
        $leaves->setOnLeave($schedule, false, '2026-10-11');
        $this->assertFalse($service->page(null, 'SEMUA', null)['schedules'][0]['is_on_leave']);
    }

    public function test_multiple_dates_can_be_saved_and_cancelled_independently(): void
    {
        $this->actingAs($this->staff());
        foreach (['2026-10-11', '2026-10-18', '2026-10-18'] as $date) {
            $this->patch(route('doctorScheduleSettings.updateLeave'), [
                ...$this->selection(), 'leave_date' => $date, 'is_on_leave' => 1,
            ])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('doctor_schedule_leaves', 2);

        $this->patch(route('doctorScheduleSettings.updateLeave'), [
            ...$this->selection(), 'is_on_leave' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('doctor_schedule_leaves', 1);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['leave_date' => '2026-10-18']);
    }

    public function test_staff_can_edit_one_leave_date_without_changing_other_dates_or_schedule_details(): void
    {
        $this->actingAs($this->staff());
        foreach (['2026-10-11', '2026-10-18'] as $date) {
            $this->patch(route('doctorScheduleSettings.updateLeave'), [
                ...$this->selection(), 'leave_date' => $date, 'is_on_leave' => 1,
            ])->assertSessionHasNoErrors();
        }
        $before = DB::table('doctor_schedule_leaves')->where('leave_date', '2026-10-11')->first();
        $this->patch(route('doctorScheduleSettings.updateLeave'), [
            ...$this->selection(), 'original_leave_date' => '2026-10-11',
            'leave_date' => '2026-10-25', 'is_on_leave' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertDatabaseCount('doctor_schedule_leaves', 2);
        $this->assertDatabaseMissing('doctor_schedule_leaves', ['leave_date' => '2026-10-11']);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['leave_date' => '2026-10-18']);
        $this->assertDatabaseHas('doctor_schedule_leaves', [
            'id' => $before->id, 'created_at' => $before->created_at, 'leave_date' => '2026-10-25',
        ]);
        $this->assertDatabaseHas('jadwal_spesialis', [
            'kd_dokter' => 'D001', 'hari_kerja' => 'AKHAD', 'jam_mulai' => '08:00', 'kuota' => 35,
        ], 'mysql_khanza');
        $schedule = DB::connection('mysql_khanza')->table('jadwal_spesialis')->first();
        $leaves = app(DoctorScheduleLeaveRepository::class);
        $this->assertCount(1, $leaves->available(collect([$schedule]), '2026-10-11'));
        $this->assertCount(0, $leaves->available(collect([$schedule]), '2026-10-25'));

        $this->patch(route('doctorScheduleSettings.updateLeave'), [
            ...$this->selection(), 'leave_date' => '2026-10-25', 'is_on_leave' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('doctor_schedule_leaves', 1);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['leave_date' => '2026-10-18']);
    }

    public function test_editing_to_an_existing_leave_date_preserves_both_records(): void
    {
        $this->actingAs($this->staff());
        foreach (['2026-10-11', '2026-10-18'] as $date) {
            $this->patch(route('doctorScheduleSettings.updateLeave'), [
                ...$this->selection(), 'leave_date' => $date, 'is_on_leave' => 1,
            ])->assertSessionHasNoErrors();
        }
        $this->patch(route('doctorScheduleSettings.updateLeave'), [
            ...$this->selection(), 'original_leave_date' => '2026-10-11',
            'leave_date' => '2026-10-18', 'is_on_leave' => 1,
        ])->assertSessionHasErrors('leave_date');
        $this->assertDatabaseCount('doctor_schedule_leaves', 2);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['leave_date' => '2026-10-11']);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['leave_date' => '2026-10-18']);
    }

    public function test_editing_a_missing_or_other_schedules_leave_does_not_create_or_remove_records(): void
    {
        $this->actingAs($this->staff());
        DB::table('doctor_schedule_leaves')->insert([
            'doctor_code' => 'D001', 'clinic_code' => 'INT', 'day' => 'MINGGU',
            'start_time' => '08:00', 'leave_date' => '2026-10-11',
        ]);
        foreach (['2026-10-11', '2026-10-18'] as $originalDate) {
            $this->patch(route('doctorScheduleSettings.updateLeave'), [
                ...$this->selection(), 'original_leave_date' => $originalDate,
                'leave_date' => '2026-10-25', 'is_on_leave' => 1,
            ])->assertSessionHasErrors('original_leave_date');
        }
        $this->assertDatabaseCount('doctor_schedule_leaves', 1);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['clinic_code' => 'INT', 'leave_date' => '2026-10-11']);
    }

    public function test_original_leave_date_must_be_valid_and_can_only_be_used_for_editing(): void
    {
        $this->actingAs($this->staff());
        foreach ([['invalid', 1], ['2026-10-11', 0]] as [$originalDate, $state]) {
            $this->patch(route('doctorScheduleSettings.updateLeave'), [
                ...$this->selection(), 'original_leave_date' => $originalDate, 'is_on_leave' => $state,
            ])->assertSessionHasErrors('original_leave_date');
        }
        $this->assertDatabaseCount('doctor_schedule_leaves', 0);
    }

    public function test_leave_date_is_required_and_rejects_invalid_and_past_dates(): void
    {
        $this->actingAs($this->staff());
        foreach ([null, 'invalid', '11/10/2026', '2026-02-30', '2026-10-10'] as $date) {
            $this->patch(route('doctorScheduleSettings.updateLeave'), [
                ...$this->selection(), 'leave_date' => $date, 'is_on_leave' => 1,
            ])->assertSessionHasErrors('leave_date');
        }
        $this->assertDatabaseCount('doctor_schedule_leaves', 0);
    }

    public function test_leave_date_must_match_the_selected_practice_day(): void
    {
        $this->actingAs($this->staff());
        $this->patch(route('doctorScheduleSettings.updateLeave'), [
            ...$this->selection(), 'leave_date' => '2026-10-12', 'is_on_leave' => 1,
        ])->assertSessionHasErrors(['leave_date' => 'Pilih tanggal Minggu sesuai jadwal praktik yang dipilih.']);
        $this->assertDatabaseCount('doctor_schedule_leaves', 0);

        $this->patch(route('doctorScheduleSettings.updateLeave'), [
            ...$this->selection(), 'leave_date' => '2026-10-18', 'is_on_leave' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('doctor_schedule_leaves', ['leave_date' => '2026-10-18']);
    }

    public function test_legacy_undated_leave_requires_a_date_and_does_not_block_every_week(): void
    {
        $schedule = DB::connection('mysql_khanza')->table('jadwal_spesialis')->first();
        DB::table('doctor_schedule_leaves')->insert([
            'doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'MINGGU', 'start_time' => '08:00',
        ]);
        $leaves = app(DoctorScheduleLeaveRepository::class);
        $annotated = $leaves->annotate(collect([[
            'doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'MINGGU', 'start_time' => '08:00',
        ]]));
        $this->assertTrue($annotated[0]['has_undated_leave']);
        $this->assertFalse($annotated[0]['is_on_leave']);
        $this->assertCount(1, $leaves->available(collect([$schedule]), '2026-10-11'));

        $leaves->setOnLeave($schedule, true, '2026-10-11');
        $this->assertDatabaseCount('doctor_schedule_leaves', 1);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['leave_date' => '2026-10-11']);
        $this->assertCount(0, $leaves->available(collect([$schedule]), '2026-10-11'));
        $this->assertCount(1, $leaves->available(collect([$schedule]), '2026-10-18'));
    }

    public function test_past_leave_dates_no_longer_mark_patient_or_admin_schedules_on_leave(): void
    {
        $schedule = DB::connection('mysql_khanza')->table('jadwal_spesialis')->first();
        $leaves = app(DoctorScheduleLeaveRepository::class);
        $leaves->setOnLeave($schedule, true, '2026-10-04');
        $annotated = $leaves->annotate(collect([[
            'doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'MINGGU', 'start_time' => '08:00',
        ]]));
        $this->assertFalse($annotated[0]['is_on_leave']);
        $this->assertSame([], $annotated[0]['leave_dates']);
        $this->assertCount(1, $leaves->available(collect([$schedule]), '2026-10-11'));
    }

    private function selection(): array
    {
        return [
            'original_doctor_code' => 'D001', 'original_day' => 'MINGGU', 'original_start_time' => '08:00',
            'leave_date' => '2026-10-11',
        ];
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->setRelation('roles', collect([new Role([
            'name' => config('access-control.super_admin_role'), 'guard_name' => 'web',
        ])]));

        return $user;
    }
}
