<?php

namespace Tests\Feature;

use App\Models\DoctorPhoto;
use App\Models\User;
use App\Repositories\epasien\settings\DoctorScheduleRepository;
use App\Services\epasien\menu\JadwalDokterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['database.connections.mysql_khanza' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('mysql_khanza');
        $schema = DB::connection('mysql_khanza')->getSchemaBuilder();
        $schema->create('dokter', function (Blueprint $table): void {
            $table->string('kd_dokter', 20)->primary();
            $table->string('nm_dokter');
            $table->string('jk');
            $table->string('status');
        });
        $schema->create('poliklinik', function (Blueprint $table): void {
            $table->string('kd_poli', 5)->primary();
            $table->string('nm_poli');
            $table->string('status');
        });
        $schema->create('jadwal_spesialis', function (Blueprint $table): void {
            $table->string('kd_dokter');
            $table->string('kd_poli');
            $table->string('hari_kerja');
            $table->string('jam_mulai');
            $table->string('jam_selesai');
            $table->integer('kuota');
            $table->primary(['kd_dokter', 'hari_kerja', 'jam_mulai']);
        });
        DB::connection('mysql_khanza')->getPdo()->sqliteCreateFunction('FIELD', function (...$values): int {
            $needle = array_shift($values);
            $position = array_search($needle, $values, true);

            return $position === false ? 0 : $position + 1;
        });
        DB::connection('mysql_khanza')->table('dokter')->insert([
            ['kd_dokter' => 'D001', 'nm_dokter' => 'dr. Sudah Terjadwal', 'jk' => 'P', 'status' => '1'],
            ['kd_dokter' => 'D002', 'nm_dokter' => 'dr. Dokter Baru', 'jk' => 'L', 'status' => '1'],
            ['kd_dokter' => 'D003', 'nm_dokter' => 'dr. Tidak Aktif', 'jk' => 'L', 'status' => '0'],
            ['kd_dokter' => 'D004', 'nm_dokter' => 'dr. Poli Nonaktif', 'jk' => 'L', 'status' => '1'],
        ]);
        DB::connection('mysql_khanza')->table('poliklinik')->insert([
            ['kd_poli' => 'ANA', 'nm_poli' => 'Poliklinik Anak', 'status' => '1'],
            ['kd_poli' => 'INT', 'nm_poli' => 'Poliklinik Penyakit Dalam', 'status' => '1'],
            ['kd_poli' => 'OLD', 'nm_poli' => 'Poliklinik Nonaktif', 'status' => '0'],
        ]);
        DB::connection('mysql_khanza')->table('jadwal_spesialis')->insert([
            ['kd_dokter' => 'D001', 'kd_poli' => 'ANA', 'hari_kerja' => 'AKHAD', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'kuota' => 25],
            ['kd_dokter' => 'D004', 'kd_poli' => 'OLD', 'hari_kerja' => 'SENIN', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'kuota' => 25],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('mysql_khanza');

        parent::tearDown();
    }

    public function test_add_options_exclude_every_scheduled_doctor_regardless_of_page_filters(): void
    {
        $response = $this->actingAs($this->staff())->get(route('doctorScheduleSettings.index', [
            'q' => 'Tidak Cocok', 'hari' => 'SELASA', 'poli' => 'INT',
        ]))->assertOk()->assertSeeText('Tambah Dokter')
            ->assertSee('<option value="D002"', false)
            ->assertDontSee('<option value="D001"', false)
            ->assertDontSee('<option value="D003"', false)
            ->assertDontSee('<option value="D004"', false);

        $this->assertSame(['D002'], $response->viewData('availableDoctors')->pluck('kd_dokter')->all());
        $this->assertSame(['ANA', 'INT'], $response->viewData('availableClinics')->pluck('kd_poli')->all());
    }

    public function test_staff_can_add_a_missing_doctor_with_clinic_day_times_and_quota(): void
    {
        $this->actingAs($this->staff())->post(route('doctorScheduleSettings.store'), $this->newSchedule())
            ->assertRedirect(route('doctorScheduleSettings.index', ['q' => 'D002']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('jadwal_spesialis', [
            'kd_dokter' => 'D002', 'kd_poli' => 'INT', 'hari_kerja' => 'AKHAD',
            'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'kuota' => 30,
        ], 'mysql_khanza');
        $this->assertSame([], app(DoctorScheduleRepository::class)->availableDoctors()->all());
    }

    public function test_duplicate_doctor_is_rejected_even_when_day_time_and_clinic_differ(): void
    {
        $this->actingAs($this->staff())->post(route('doctorScheduleSettings.store'), [
            ...$this->newSchedule(), 'doctor_code' => 'D001', 'day' => 'SELASA',
        ])->assertSessionHasErrors('doctor_code');

        $this->assertDatabaseCount('jadwal_spesialis', 2, 'mysql_khanza');
        $this->assertDatabaseHas('jadwal_spesialis', ['kd_dokter' => 'D001', 'kuota' => 25], 'mysql_khanza');
    }

    public function test_unknown_inactive_and_already_scheduled_doctors_cannot_be_submitted(): void
    {
        $this->actingAs($this->staff());

        foreach (['D003', 'D004', 'D404', ''] as $doctorCode) {
            $this->post(route('doctorScheduleSettings.store'), [...$this->newSchedule(), 'doctor_code' => $doctorCode])
                ->assertSessionHasErrors('doctor_code');
        }

        $this->assertDatabaseCount('jadwal_spesialis', 2, 'mysql_khanza');
    }

    public function test_invalid_clinic_times_day_and_quota_are_rejected(): void
    {
        $this->actingAs($this->staff());

        foreach ([
            ['clinic_code', 'OLD'], ['clinic_code', 'BAD'], ['end_time', '12:00'],
            ['day', 'INVALID'], ['start_time', '25:00'], ['quota', -1], ['quota', 10000],
        ] as [$field, $value]) {
            $this->post(route('doctorScheduleSettings.store'), [...$this->newSchedule(), $field => $value])
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('jadwal_spesialis', 2, 'mysql_khanza');
    }

    public function test_staff_can_delete_only_the_selected_schedule_and_its_leave_record(): void
    {
        DB::connection('mysql_khanza')->table('jadwal_spesialis')->insert([
            'kd_dokter' => 'D001', 'kd_poli' => 'ANA', 'hari_kerja' => 'SENIN',
            'jam_mulai' => '09:00:00', 'jam_selesai' => '11:00:00', 'kuota' => 20,
        ]);
        DB::table('doctor_schedule_leaves')->insert([
            ['doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'MINGGU', 'start_time' => '08:00', 'created_at' => now(), 'updated_at' => now()],
            ['doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'SENIN', 'start_time' => '09:00', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DoctorPhoto::query()->create(['doctor_code' => 'D001', 'image_path' => 'doctor-photos/keep.webp']);

        $this->actingAs($this->staff())->get(route('doctorScheduleSettings.index'))
            ->assertOk()->assertSee(route('doctorScheduleSettings.destroy'), false)->assertSeeText('Hapus');
        $this->delete(route('doctorScheduleSettings.destroy'), $this->selection())
            ->assertRedirect(route('doctorScheduleSettings.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('jadwal_spesialis', ['kd_dokter' => 'D001', 'hari_kerja' => 'AKHAD'], 'mysql_khanza');
        $this->assertDatabaseHas('jadwal_spesialis', ['kd_dokter' => 'D001', 'hari_kerja' => 'SENIN'], 'mysql_khanza');
        $this->assertDatabaseMissing('doctor_schedule_leaves', ['day' => 'MINGGU']);
        $this->assertDatabaseHas('doctor_schedule_leaves', ['day' => 'SENIN']);
        $this->assertDatabaseHas('dokter', ['kd_dokter' => 'D001', 'status' => '1'], 'mysql_khanza');
        $this->assertDatabaseHas('doctor_photos', ['doctor_code' => 'D001', 'image_path' => 'doctor-photos/keep.webp']);
        $this->assertSame(['D002'], app(DoctorScheduleRepository::class)->availableDoctors()->pluck('kd_dokter')->all());
    }

    public function test_doctor_can_be_added_again_after_deleting_the_last_schedule(): void
    {
        $this->actingAs($this->staff())->delete(route('doctorScheduleSettings.destroy'), $this->selection())
            ->assertSessionHasNoErrors();

        $this->assertContains('D001', app(DoctorScheduleRepository::class)->availableDoctors()->pluck('kd_dokter')->all());
        $this->post(route('doctorScheduleSettings.store'), [...$this->newSchedule(), 'doctor_code' => 'D001'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('jadwal_spesialis', ['kd_dokter' => 'D001', 'kd_poli' => 'INT'], 'mysql_khanza');
    }

    public function test_stale_or_incorrect_schedule_identity_cannot_delete_another_schedule(): void
    {
        $this->actingAs($this->staff());

        foreach ([['original_clinic_code', 'INT'], ['original_start_time', '16:00'], ['original_day', 'SELASA']] as [$field, $value]) {
            $this->delete(route('doctorScheduleSettings.destroy'), [...$this->selection(), $field => $value])
                ->assertSessionHasErrors('schedule');
        }

        $this->assertDatabaseCount('jadwal_spesialis', 2, 'mysql_khanza');
    }

    public function test_guest_and_patient_cannot_add_or_delete_schedules(): void
    {
        $this->post(route('doctorScheduleSettings.store'), $this->newSchedule())->assertRedirect(route('login'));
        $this->delete(route('doctorScheduleSettings.destroy'), $this->selection())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->post(route('doctorScheduleSettings.store'), $this->newSchedule())->assertForbidden();
        $this->delete(route('doctorScheduleSettings.destroy'), $this->selection())->assertForbidden();
        $this->assertDatabaseCount('jadwal_spesialis', 2, 'mysql_khanza');
    }

    public function test_failed_schedule_deletion_preserves_its_leave_status(): void
    {
        DB::table('doctor_schedule_leaves')->insert([
            'doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'MINGGU',
            'start_time' => '08:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::connection('mysql_khanza')->unprepared(
            "CREATE TRIGGER prevent_schedule_delete BEFORE DELETE ON jadwal_spesialis BEGIN SELECT RAISE(ABORT, 'delete blocked'); END"
        );

        $this->actingAs($this->staff())->delete(route('doctorScheduleSettings.destroy'), $this->selection())
            ->assertSessionHasErrors('schedule');

        $this->assertDatabaseHas('jadwal_spesialis', ['kd_dokter' => 'D001'], 'mysql_khanza');
        $this->assertDatabaseHas('doctor_schedule_leaves', ['doctor_code' => 'D001', 'day' => 'MINGGU']);
    }

    public function test_add_and_delete_refresh_cached_patient_schedules_summary_clinics_and_today(): void
    {
        $this->travelTo(now('Asia/Jakarta')->startOfWeek()->setTime(8, 0));
        $service = app(JadwalDokterService::class);
        $before = $service->page(null, 'SEMUA', null);
        $this->assertSame(1, $before['schedules']->total());
        $this->assertCount(0, $service->today());

        $this->actingAs($this->staff())->post(route('doctorScheduleSettings.store'), [
            ...$this->newSchedule(), 'day' => 'SENIN',
        ])->assertSessionHasNoErrors();

        $after = $service->page(null, 'SEMUA', null);
        $this->assertSame(2, $after['schedules']->total());
        $this->assertSame(2, $after['summary']['doctors']);
        $this->assertCount(2, $after['clinics']);
        $this->assertSame(['D002'], $service->today()->pluck('doctor_code')->all());

        $this->delete(route('doctorScheduleSettings.destroy'), [
            'original_doctor_code' => 'D002', 'original_clinic_code' => 'INT',
            'original_day' => 'SENIN', 'original_start_time' => '13:00',
        ])->assertSessionHasNoErrors();

        $afterDelete = $service->page(null, 'SEMUA', null);
        $this->assertSame(1, $afterDelete['schedules']->total());
        $this->assertSame(1, $afterDelete['summary']['doctors']);
        $this->assertCount(1, $afterDelete['clinics']);
        $this->assertCount(0, $service->today());
    }

    private function newSchedule(): array
    {
        return ['doctor_code' => 'D002', 'clinic_code' => 'INT', 'day' => 'MINGGU', 'start_time' => '13:00', 'end_time' => '15:00', 'quota' => 30];
    }

    private function selection(): array
    {
        return ['original_doctor_code' => 'D001', 'original_clinic_code' => 'ANA', 'original_day' => 'MINGGU', 'original_start_time' => '08:00'];
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
