<?php

namespace App\Events;

use App\Models\PatientServiceMessage;
use App\Services\epasien\menu\PatientServiceChatService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientServiceMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public PatientServiceMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patient-service.conversation.'.$this->message->conversation_id),
            new PrivateChannel('patient-service.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'patient-service.message.sent';
    }

    public function broadcastWith(): array
    {
        $service = app(PatientServiceChatService::class);

        return [
            'message' => $service->messagePayload($this->message),
            'conversation' => $service->conversationPayload($this->message->conversation),
        ];
    }
}
