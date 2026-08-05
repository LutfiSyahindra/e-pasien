<?php

namespace App\Events;

use App\Models\PatientServiceConversation;
use App\Services\epasien\menu\PatientServiceChatService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientServiceConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public PatientServiceConversation $conversation) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patient-service.conversation.'.$this->conversation->id),
            new PrivateChannel('patient-service.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'patient-service.conversation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => app(PatientServiceChatService::class)
                ->conversationPayload($this->conversation),
        ];
    }
}
