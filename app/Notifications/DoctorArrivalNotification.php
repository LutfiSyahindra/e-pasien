<?php

namespace App\Notifications;

use App\Models\DoctorArrivalEvent;
use App\Models\DoctorPhoto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DoctorArrivalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly array $arrival)
    {
        $this->afterCommit();
    }

    public static function fromEvent(DoctorArrivalEvent $event): self
    {
        $doctorName = trim((string) $event->doctor_name) ?: 'Dokter Anda';
        $clinicName = trim((string) $event->clinic_name);
        $location = $clinicName !== '' ? ' di '.$clinicName : '';
        $doctorPhoto = DoctorPhoto::query()
            ->where('doctor_code', trim((string) $event->doctor_code))
            ->first();

        return new self([
            'event_id' => $event->getKey(),
            'service_date' => $event->service_date->toDateString(),
            'doctor_code' => $event->doctor_code,
            'clinic_code' => $event->clinic_code,
            'doctor_name' => $doctorName,
            'clinic_name' => $clinicName,
            'image_url' => $doctorPhoto?->image_url,
            'body' => $doctorName.' sudah datang'.$location.'. Silakan bersiap untuk pemeriksaan Anda.',
            'url' => route('daftarOnline.index', absolute: false),
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
            'kind' => 'doctor_arrival',
            'title' => 'Dokter sudah datang',
            'body' => $this->arrival['body'],
            'url' => $this->arrival['url'],
            'doctor_arrival_event_id' => $this->arrival['event_id'],
            'service_date' => $this->arrival['service_date'],
            'doctor_code' => $this->arrival['doctor_code'],
            'clinic_code' => $this->arrival['clinic_code'],
            'doctor_name' => $this->arrival['doctor_name'],
            'clinic_name' => $this->arrival['clinic_name'],
            'image_url' => $this->arrival['image_url'],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'doctor.arrived';
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $imageUrl = trim((string) ($this->arrival['image_url'] ?? ''));
        $message = (new WebPushMessage)
            ->title('Dokter sudah datang')
            ->body($this->arrival['body'])
            ->icon($imageUrl !== '' ? $imageUrl : '/epasien/assets/images/pwa-icon-192.png')
            ->badge('/epasien/assets/images/favicon-32x32.png')
            ->tag('doctor-arrival-'.$this->arrival['event_id'])
            ->renotify()
            ->vibrate([220, 80, 220])
            ->action('Lihat pendaftaran', 'open_registration')
            ->data([
                'kind' => 'doctor_arrival',
                'url' => $this->arrival['url'],
                'doctor_arrival_event_id' => $this->arrival['event_id'],
            ])
            ->options(['TTL' => 21600]);

        if ($imageUrl !== '') {
            $message->image($imageUrl);
        }

        return $message;
    }
}
