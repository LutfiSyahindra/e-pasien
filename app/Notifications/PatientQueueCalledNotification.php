<?php

namespace App\Notifications;

use App\Models\DoctorPhoto;
use App\Models\PatientQueueCallEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PatientQueueCalledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly array $queueCall)
    {
        $this->afterCommit();
    }

    public static function fromEvent(PatientQueueCallEvent $event): self
    {
        $queueNumber = trim((string) $event->queue_number) ?: '-';
        $clinicName = trim((string) $event->clinic_name);
        $doctorName = trim((string) $event->doctor_name);
        $location = $clinicName !== '' ? ' di '.$clinicName : '';
        $doctor = $doctorName !== '' ? ' bersama '.$doctorName : '';
        $doctorPhoto = DoctorPhoto::query()
            ->where('doctor_code', trim((string) $event->doctor_code))
            ->first();

        return new self([
            'event_id' => $event->getKey(),
            'service_date' => $event->service_date->toDateString(),
            'visit_number' => $event->visit_number,
            'queue_number' => $queueNumber,
            'doctor_code' => $event->doctor_code,
            'clinic_code' => $event->clinic_code,
            'doctor_name' => $doctorName,
            'clinic_name' => $clinicName,
            'image_url' => $doctorPhoto?->image_url,
            'body' => 'Nomor antrean Anda '.$queueNumber.' sedang dipanggil'.$location.$doctor.'. Silakan menuju poli sekarang.',
            'url' => route('dashboard', absolute: false).'#dashboard-queue-title',
        ]);
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if (config('webpush.vapid.public_key') && config('webpush.vapid.private_key')) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'patient_queue_called',
            'title' => 'Giliran antrean Anda',
            'body' => $this->queueCall['body'],
            'url' => $this->queueCall['url'],
            'patient_queue_call_event_id' => $this->queueCall['event_id'],
            'service_date' => $this->queueCall['service_date'],
            'visit_number' => $this->queueCall['visit_number'],
            'queue_number' => $this->queueCall['queue_number'],
            'doctor_code' => $this->queueCall['doctor_code'],
            'clinic_code' => $this->queueCall['clinic_code'],
            'doctor_name' => $this->queueCall['doctor_name'],
            'clinic_name' => $this->queueCall['clinic_name'],
            'image_url' => $this->queueCall['image_url'],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'patient.queue.called';
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $imageUrl = trim((string) ($this->queueCall['image_url'] ?? ''));
        $message = (new WebPushMessage)
            ->title('Giliran antrean Anda')
            ->body($this->queueCall['body'])
            ->icon($imageUrl !== '' ? $imageUrl : '/epasien/assets/images/pwa-icon-192.png')
            ->badge('/epasien/assets/images/favicon-32x32.png')
            ->tag('patient-queue-called-'.$this->queueCall['event_id'])
            ->renotify()
            ->requireInteraction()
            ->vibrate([300, 100, 300, 100, 500])
            ->action('Lihat antrean', 'open_patient_queue')
            ->data([
                'kind' => 'patient_queue_called',
                'url' => $this->queueCall['url'],
                'patient_queue_call_event_id' => $this->queueCall['event_id'],
                'queue_number' => $this->queueCall['queue_number'],
            ])
            ->options(['TTL' => 900, 'urgency' => 'high']);

        if ($imageUrl !== '') {
            $message->image($imageUrl);
        }

        return $message;
    }
}
