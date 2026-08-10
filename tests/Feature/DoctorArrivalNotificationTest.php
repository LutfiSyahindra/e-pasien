<?php

namespace Tests\Feature;

use App\Jobs\DispatchDoctorArrivalNotification;
use App\Models\DoctorArrivalEvent;
use App\Models\DoctorPhoto;
use App\Models\User;
use App\Notifications\DoctorArrivalNotification;
use App\Repositories\epasien\menu\DoctorArrivalRepository;
use App\Services\epasien\menu\DoctorArrivalNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use PDO;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorArrivalNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Driver PDO SQLite tidak tersedia pada lingkungan pengujian.');
        }

        $this->artisan('migrate:fresh');
    }

    public function test_two_examination_rows_detect_the_doctor_schedule_and_cancelled_visits_are_ignored(): void
    {
        $this->useInMemoryKhanzaConnection();
        $this->createKhanzaArrivalTables();

        DB::connection('mysql_khanza')->table('dokter')->insert([
            ['kd_dokter' => 'D001', 'nm_dokter' => 'dr. Sehat'],
            ['kd_dokter' => 'D002', 'nm_dokter' => 'dr. Belum'],
            ['kd_dokter' => 'D003', 'nm_dokter' => 'dr. Batal'],
        ]);
        DB::connection('mysql_khanza')->table('poliklinik')->insert([
            ['kd_poli' => 'ANA', 'nm_poli' => 'Poli Anak'],
            ['kd_poli' => 'INT', 'nm_poli' => 'Poli Penyakit Dalam'],
        ]);
        DB::connection('mysql_khanza')->table('reg_periksa')->insert([
            [
                'no_rawat' => '2026/08/07/000001',
                'no_reg' => '007',
                'tgl_registrasi' => '2026-08-07',
                'kd_dokter' => 'D001',
                'kd_poli' => 'ANA',
                'no_rkm_medis' => '000123',
                'stts' => 'Belum',
            ],
            [
                'no_rawat' => '2026/08/07/000002',
                'no_reg' => '003',
                'tgl_registrasi' => '2026-08-07',
                'kd_dokter' => 'D002',
                'kd_poli' => 'INT',
                'no_rkm_medis' => '000456',
                'stts' => 'Belum',
            ],
            [
                'no_rawat' => '2026/08/07/000004',
                'no_reg' => '008',
                'tgl_registrasi' => '2026-08-07',
                'kd_dokter' => 'D001',
                'kd_poli' => 'ANA',
                'no_rkm_medis' => '000124',
                'stts' => 'Belum',
            ],
            [
                'no_rawat' => '2026/08/07/000003',
                'no_reg' => '009',
                'tgl_registrasi' => '2026-08-07',
                'kd_dokter' => 'D003',
                'kd_poli' => 'ANA',
                'no_rkm_medis' => '000789',
                'stts' => 'Batal',
            ],
        ]);
        DB::connection('mysql_khanza')->table('pemeriksaan_ralan')->insert([
            ['no_rawat' => '2026/08/07/000001', 'tgl_perawatan' => '2026-08-07', 'jam_rawat' => '08:01:00'],
            ['no_rawat' => '2026/08/07/000001', 'tgl_perawatan' => '2026-08-07', 'jam_rawat' => '08:05:00'],
            ['no_rawat' => '2026/08/07/000002', 'tgl_perawatan' => '2026-08-07', 'jam_rawat' => '08:02:00'],
            ['no_rawat' => '2026/08/07/000003', 'tgl_perawatan' => '2026-08-07', 'jam_rawat' => '08:03:00'],
            ['no_rawat' => '2026/08/07/000003', 'tgl_perawatan' => '2026-08-07', 'jam_rawat' => '08:04:00'],
        ]);

        $repository = app(DoctorArrivalRepository::class);
        $schedules = $repository->detectedSchedules('2026-08-07');
        $queues = $repository->currentQueues('2026-08-07');
        $calledPatients = $repository->calledPatientQueues('2026-08-07');

        $this->assertCount(1, $schedules);
        $this->assertSame('D001', $schedules->first()->doctor_code);
        $this->assertSame('ANA', $schedules->first()->clinic_code);
        $this->assertSame('dr. Sehat', $schedules->first()->doctor_name);
        $this->assertSame('008', $queues->sole()->queue_number);
        $this->assertSame('007', $queues->sole()->last_serviced_number);
        $this->assertSame('calling', $queues->sole()->queue_state);
        $this->assertSame(
            '2026-08-07 08:05:00',
            $queues->sole()->serviced_at
        );
        $this->assertSame(
            ['000123', '000124'],
            $repository->medicalRecordNumbersForSchedule('2026-08-07', 'D001', 'ANA')->all()
        );
        $this->assertSame('2026/08/07/000004', $calledPatients->sole()->visit_number);
        $this->assertSame('008', $calledPatients->sole()->queue_number);
        $this->assertSame('000124', $calledPatients->sole()->medical_record_number);
        $this->assertSame('dr. Sehat', $calledPatients->sole()->doctor_name);
        $this->assertSame('Poli Anak', $calledPatients->sole()->clinic_name);
    }

    public function test_detected_schedule_is_queued_only_once(): void
    {
        $role = Role::create(['name' => 'Pasien', 'guard_name' => 'web']);
        $role->forceFill(['doctor_arrival_notifications_enabled' => true])->save();
        $repository = $this->createMock(DoctorArrivalRepository::class);
        $repository
            ->expects($this->exactly(2))
            ->method('detectedSchedules')
            ->with('2026-08-07')
            ->willReturn(collect([(object) [
                'doctor_code' => 'D001',
                'clinic_code' => 'ANA',
                'doctor_name' => 'dr. Sehat',
                'clinic_name' => 'Poli Anak',
            ]]));
        Bus::fake();

        $service = new DoctorArrivalNotificationService($repository);

        $this->assertSame(1, $service->dispatchDetected('2026-08-07'));
        $this->assertSame(0, $service->dispatchDetected('2026-08-07'));
        $this->assertDatabaseCount('doctor_arrival_events', 1);
        $this->assertNotNull(DoctorArrivalEvent::query()->sole()->queued_at);
        Bus::assertDispatchedTimes(DispatchDoctorArrivalNotification::class, 1);
    }

    public function test_dispatch_notifies_only_active_scheduled_users_from_enabled_roles(): void
    {
        config([
            'webpush.vapid.public_key' => 'public-test-key',
            'webpush.vapid.private_key' => 'private-test-key',
        ]);
        NotificationFacade::fake();

        $enabledRole = Role::create(['name' => 'Pasien', 'guard_name' => 'web']);
        $enabledRole->forceFill(['doctor_arrival_notifications_enabled' => true])->save();
        $disabledRole = Role::create(['name' => 'Tamu', 'guard_name' => 'web']);

        $eligible = User::factory()->create(['username' => '000123', 'status' => true]);
        $eligible->assignRole($enabledRole);
        $inactive = User::factory()->create(['username' => '000456', 'status' => false]);
        $inactive->assignRole($enabledRole);
        $disabled = User::factory()->create(['username' => '000789', 'status' => true]);
        $disabled->assignRole($disabledRole);
        $otherSchedule = User::factory()->create(['username' => '000999', 'status' => true]);
        $otherSchedule->assignRole($enabledRole);

        $event = DoctorArrivalEvent::query()->create([
            'service_date' => '2026-08-07',
            'doctor_code' => 'D001',
            'clinic_code' => 'ANA',
            'doctor_name' => 'dr. Sehat',
            'clinic_name' => 'Poli Anak',
            'detected_at' => now(),
            'queued_at' => now(),
        ]);
        DoctorPhoto::query()->create([
            'doctor_code' => 'D001',
            'image_path' => 'doctor-photos/d001.webp',
            'uploaded_by' => null,
        ]);
        $repository = $this->createMock(DoctorArrivalRepository::class);
        $repository
            ->expects($this->once())
            ->method('medicalRecordNumbersForSchedule')
            ->with('2026-08-07', 'D001', 'ANA')
            ->willReturn(collect(['000123', '000456', '000789']));

        (new DispatchDoctorArrivalNotification($event->getKey()))->handle($repository);

        NotificationFacade::assertSentTo(
            $eligible,
            DoctorArrivalNotification::class,
            function (DoctorArrivalNotification $notification, array $channels) use ($eligible, $event): bool {
                $data = $notification->toArray($eligible);
                $push = $notification->toWebPush($eligible, $notification)->toArray();

                return in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true)
                    && in_array(WebPushChannel::class, $channels, true)
                    && $data['kind'] === 'doctor_arrival'
                    && $data['title'] === 'Dokter sudah datang'
                    && $data['doctor_arrival_event_id'] === $event->getKey()
                    && $data['image_url'] === '/storage/doctor-photos/d001.webp'
                    && str_contains($data['body'], 'dr. Sehat sudah datang di Poli Anak')
                    && $push['icon'] === '/storage/doctor-photos/d001.webp'
                    && $push['image'] === '/storage/doctor-photos/d001.webp'
                    && $push['data']['kind'] === 'doctor_arrival'
                    && $push['data']['url'] === route('daftarOnline.index', absolute: false);
            },
        );
        NotificationFacade::assertNotSentTo($inactive, DoctorArrivalNotification::class);
        NotificationFacade::assertNotSentTo($disabled, DoctorArrivalNotification::class);
        NotificationFacade::assertNotSentTo($otherSchedule, DoctorArrivalNotification::class);
        $this->assertSame(1, $event->refresh()->recipients_count);
        $this->assertNotNull($event->dispatched_at);
    }

    public function test_notification_center_uses_the_current_doctor_photo_for_existing_notifications(): void
    {
        $patient = User::factory()->create();
        DoctorPhoto::query()->create([
            'doctor_code' => 'D001',
            'image_path' => 'doctor-photos/current-d001.webp',
            'uploaded_by' => null,
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => DoctorArrivalNotification::class,
            'notifiable_type' => $patient->getMorphClass(),
            'notifiable_id' => $patient->getKey(),
            'data' => [
                'kind' => 'doctor_arrival',
                'title' => 'Dokter sudah datang',
                'doctor_code' => 'D001',
                'image_url' => null,
            ],
        ]);

        $this->actingAs($patient)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath(
                'notifications.0.data.image_url',
                '/storage/doctor-photos/current-d001.webp'
            );
    }

    private function useInMemoryKhanzaConnection(): void
    {
        config([
            'database.connections.mysql_khanza' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('mysql_khanza');
    }

    private function createKhanzaArrivalTables(): void
    {
        Schema::connection('mysql_khanza')->create('dokter', function (Blueprint $table): void {
            $table->string('kd_dokter')->primary();
            $table->string('nm_dokter');
        });
        Schema::connection('mysql_khanza')->create('poliklinik', function (Blueprint $table): void {
            $table->string('kd_poli')->primary();
            $table->string('nm_poli');
        });
        Schema::connection('mysql_khanza')->create('reg_periksa', function (Blueprint $table): void {
            $table->string('no_rawat')->primary();
            $table->string('no_reg');
            $table->date('tgl_registrasi');
            $table->string('kd_dokter');
            $table->string('kd_poli');
            $table->string('no_rkm_medis');
            $table->string('stts');
        });
        Schema::connection('mysql_khanza')->create('pemeriksaan_ralan', function (Blueprint $table): void {
            $table->id();
            $table->string('no_rawat');
            $table->date('tgl_perawatan')->nullable();
            $table->time('jam_rawat')->nullable();
        });
    }
}
