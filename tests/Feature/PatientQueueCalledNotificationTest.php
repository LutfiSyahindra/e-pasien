<?php

namespace Tests\Feature;

use App\Jobs\DispatchPatientQueueCalledNotification;
use App\Models\DoctorPhoto;
use App\Models\PatientQueueCallEvent;
use App\Models\User;
use App\Notifications\PatientQueueCalledNotification;
use App\Repositories\epasien\menu\DoctorArrivalRepository;
use App\Services\epasien\menu\PatientQueueNotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use PDO;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientQueueCalledNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Driver PDO SQLite tidak tersedia pada lingkungan pengujian.');
        }

        $this->artisan('migrate:fresh');
    }

    public function test_called_patient_is_queued_only_once(): void
    {
        $repository = $this->createMock(DoctorArrivalRepository::class);
        $repository
            ->expects($this->exactly(2))
            ->method('calledPatientQueues')
            ->with('2026-08-10')
            ->willReturn(collect([(object) [
                'visit_number' => '2026/08/10/000123',
                'medical_record_number' => '000124',
                'queue_number' => '008',
                'doctor_code' => 'D001',
                'clinic_code' => 'ANA',
                'doctor_name' => 'dr. Sehat',
                'clinic_name' => 'Poli Anak',
                'called_at' => '2026-08-10 08:05:00',
            ]]));
        Bus::fake();

        $service = new PatientQueueNotificationService($repository);

        $this->assertSame(1, $service->dispatchDetected('2026-08-10'));
        $this->assertSame(0, $service->dispatchDetected('2026-08-10'));
        $this->assertDatabaseCount('patient_queue_call_events', 1);

        $event = PatientQueueCallEvent::query()->sole();
        $this->assertNotNull($event->queued_at);
        $this->assertSame('000124', $event->medical_record_number);
        $this->assertSame('008', $event->queue_number);
        $this->assertSame('2026-08-10 01:05:00', $event->called_at->utc()->format('Y-m-d H:i:s'));
        Bus::assertDispatchedTimes(DispatchPatientQueueCalledNotification::class, 1);
    }

    public function test_dispatch_notifies_only_the_matching_active_patient(): void
    {
        config([
            'webpush.vapid.public_key' => 'public-test-key',
            'webpush.vapid.private_key' => 'private-test-key',
        ]);
        NotificationFacade::fake();

        $patientRole = Role::create([
            'name' => config('access-control.patient_role', 'Patient'),
            'guard_name' => 'web',
        ]);
        $eligible = User::factory()->create(['username' => '000124', 'status' => true]);
        $eligible->assignRole($patientRole);
        $otherPatient = User::factory()->create(['username' => '000999', 'status' => true]);
        $otherPatient->assignRole($patientRole);

        $event = PatientQueueCallEvent::query()->create([
            'service_date' => '2026-08-10',
            'visit_number' => '2026/08/10/000123',
            'medical_record_number' => '000124',
            'queue_number' => '008',
            'doctor_code' => 'D001',
            'clinic_code' => 'ANA',
            'doctor_name' => 'dr. Sehat',
            'clinic_name' => 'Poli Anak',
            'called_at' => now(),
            'detected_at' => now(),
            'queued_at' => now(),
        ]);
        DoctorPhoto::query()->create([
            'doctor_code' => 'D001',
            'image_path' => 'doctor-photos/d001.webp',
            'uploaded_by' => null,
        ]);

        (new DispatchPatientQueueCalledNotification($event->getKey()))->handle();

        NotificationFacade::assertSentTo(
            $eligible,
            PatientQueueCalledNotification::class,
            function (PatientQueueCalledNotification $notification, array $channels) use ($eligible, $event): bool {
                $data = $notification->toArray($eligible);
                $pushMessage = $notification->toWebPush($eligible, $notification);
                $push = $pushMessage->toArray();

                return in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true)
                    && in_array(WebPushChannel::class, $channels, true)
                    && $data['kind'] === 'patient_queue_called'
                    && $data['title'] === 'Giliran antrean Anda'
                    && $data['patient_queue_call_event_id'] === $event->getKey()
                    && $data['queue_number'] === '008'
                    && str_contains($data['body'], 'Nomor antrean Anda 008 sedang dipanggil')
                    && $data['url'] === '/dashboard#dashboard-queue-title'
                    && $push['icon'] === '/storage/doctor-photos/d001.webp'
                    && $push['image'] === '/storage/doctor-photos/d001.webp'
                    && $push['requireInteraction'] === true
                    && $push['data']['kind'] === 'patient_queue_called'
                    && $pushMessage->getOptions() === ['TTL' => 900, 'urgency' => 'high'];
            },
        );
        NotificationFacade::assertNotSentTo($otherPatient, PatientQueueCalledNotification::class);
        $this->assertSame(1, $event->refresh()->recipients_count);
        $this->assertNotNull($event->dispatched_at);
    }

    public function test_notification_center_uses_the_current_doctor_photo(): void
    {
        $patient = User::factory()->create();
        DoctorPhoto::query()->create([
            'doctor_code' => 'D001',
            'image_path' => 'doctor-photos/current-d001.webp',
            'uploaded_by' => null,
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => PatientQueueCalledNotification::class,
            'notifiable_type' => $patient->getMorphClass(),
            'notifiable_id' => $patient->getKey(),
            'data' => [
                'kind' => 'patient_queue_called',
                'title' => 'Giliran antrean Anda',
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
}
