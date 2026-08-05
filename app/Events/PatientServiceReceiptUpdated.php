<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientServiceReceiptUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public readonly int $conversationId,
        public readonly array $messageIds,
        public readonly string $status,
        public readonly int $actorId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patient-service.conversation.'.$this->conversationId),
            new PrivateChannel('patient-service.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'patient-service.receipt.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_ids' => $this->messageIds,
            'status' => $this->status,
            'actor_id' => $this->actorId,
        ];
    }
}
