<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PatientServiceMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly array $message)
    {
        $this->afterCommit();
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
            'kind' => 'patient_service_message',
            'title' => $this->message['title'],
            'body' => $this->message['body'],
            'url' => $this->message['url'],
            'conversation_id' => $this->message['conversation_id'],
            'message_id' => $this->message['message_id'],
            'sender_id' => $this->message['sender_id'],
            'sender_name' => $this->message['sender_name'],
            'subject' => $this->message['subject'],
            'category_label' => $this->message['category_label'],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'patient-service.message';
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->message['push_title'])
            ->body($this->message['push_body'])
            ->icon('/epasien/assets/images/pwa-icon-192.png')
            ->badge('/epasien/assets/images/favicon-32x32.png')
            ->tag('patient-service-'.$this->message['conversation_id'])
            ->renotify()
            ->vibrate([180, 80, 180])
            ->action('Buka percakapan', 'open_patient_service')
            ->data(['url' => $this->message['url']])
            ->options(['TTL' => 86400]);
    }
}
