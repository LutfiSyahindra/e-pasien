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

    public function test_staff_can_toggle_selected_schedule_without_changing_practice_details(): void
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

    public function test_leave_is_refreshed_after_cached_patient_schedules_and_only_matches_selected_slot(): void
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
        $leaves->setOnLeave($schedule, true);
        $page = $service->page(null, 'SEMUA', null);
        $this->assertSame([true, false, false, false, false], $page['schedules']->pluck('is_on_leave')->all());
        $leaves->setOnLeave($schedule, false);
        $this->assertFalse($service->page(null, 'SEMUA', null)['schedules'][0]['is_on_leave']);
    }

    private function selection(): array
    {
        return ['original_doctor_code' => 'D001', 'original_day' => 'MINGGU', 'original_start_time' => '08:00'];
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
