<?php

namespace App\Notifications;

use App\Models\Promotion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PromotionPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly array $promotion)
    {
        $this->afterCommit();
    }

    public static function fromPromotion(Promotion $promotion): self
    {
        return new self([
            'id' => $promotion->getKey(),
            'category' => $promotion->category,
            'category_label' => $promotion->category_label,
            'title' => $promotion->title,
            'body' => Str::limit(strip_tags($promotion->caption), 150),
            'image_url' => $promotion->image_url,
            'url' => route('promotions.show', $promotion, absolute: false),
            'ends_at' => $promotion->ends_at->toIso8601String(),
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
            'kind' => 'promotion',
            'category' => $this->promotion['category'],
            'category_label' => $this->promotion['category_label'],
            'title' => $this->promotion['category_label'].': '.$this->promotion['title'],
            'body' => $this->promotion['body'],
            'image_url' => $this->promotion['image_url'],
            'url' => $this->promotion['url'],
            'promotion_id' => $this->promotion['id'],
            'ends_at' => $this->promotion['ends_at'],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'promotion.published';
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->promotion['category_label'].' Terbaru')
            ->body($this->promotion['title'].' — '.$this->promotion['body'])
            ->icon('/epasien/assets/images/logo-icon.png')
            ->badge('/epasien/assets/images/favicon-32x32.png')
            ->image($this->promotion['image_url'])
            ->tag('promotion-'.$this->promotion['id'])
            ->renotify()
            ->vibrate([180, 80, 180])
            ->action('Lihat detail', 'open_promotion')
            ->data(['url' => $this->promotion['url']])
            ->options(['TTL' => 86400]);
    }
}
