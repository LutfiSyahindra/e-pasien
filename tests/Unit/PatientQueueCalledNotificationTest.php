<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\PatientQueueCalledNotification;
use NotificationChannels\WebPush\WebPushChannel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PatientQueueCalledNotificationTest extends TestCase
{
    #[Test]
    public function it_builds_in_app_broadcast_and_high_priority_push_payloads(): void
    {
        config([
            'webpush.vapid.public_key' => 'public-test-key',
            'webpush.vapid.private_key' => 'private-test-key',
        ]);
        $user = new User;
        $notification = new PatientQueueCalledNotification([
            'event_id' => 19,
            'service_date' => '2026-08-10',
            'visit_number' => '2026/08/10/000123',
            'queue_number' => '008',
            'doctor_code' => 'D001',
            'clinic_code' => 'ANA',
            'doctor_name' => 'dr. Sehat',
            'clinic_name' => 'Poli Anak',
            'image_url' => null,
            'body' => 'Nomor antrean Anda 008 sedang dipanggil di Poli Anak. Silakan menuju poli sekarang.',
            'url' => '/dashboard#dashboard-queue-title',
        ]);

        $this->assertSame(['database', 'broadcast', WebPushChannel::class], $notification->via($user));
        $this->assertSame('patient.queue.called', $notification->broadcastType());
        $this->assertSame('patient_queue_called', $notification->toArray($user)['kind']);

        $push = $notification->toWebPush($user, $notification);
        $payload = $push->toArray();

        $this->assertSame('Giliran antrean Anda', $payload['title']);
        $this->assertSame('patient-queue-called-19', $payload['tag']);
        $this->assertTrue($payload['requireInteraction']);
        $this->assertSame('/dashboard#dashboard-queue-title', $payload['data']['url']);
        $this->assertSame(['TTL' => 900, 'urgency' => 'high'], $push->getOptions());
    }

    #[Test]
    public function frontend_recognizes_queue_calls_and_keeps_push_visible(): void
    {
        $notificationCenter = file_get_contents(resource_path('js/notification-center.js'));
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('patient_queue_call_event_id', $notificationCenter);
        $this->assertStringContainsString("data.kind === 'patient_queue_called'", $notificationCenter);
        $this->assertStringContainsString('requireInteraction: Boolean(payload.requireInteraction)', $serviceWorker);
    }
}
