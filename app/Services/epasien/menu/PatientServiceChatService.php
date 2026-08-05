<?php

namespace App\Services\epasien\menu;

use App\Events\PatientServiceConversationUpdated;
use App\Events\PatientServiceMessageSent;
use App\Models\PatientServiceConversation;
use App\Models\PatientServiceMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PatientServiceChatService
{
    public function __construct(
        private readonly PatientServiceNotificationService $notificationService,
    ) {}

    public function createConversation(User $patient, array $data): PatientServiceConversation
    {
        [$conversation, $message] = DB::transaction(function () use ($patient, $data): array {
            $conversation = PatientServiceConversation::query()->create([
                'patient_id' => $patient->id,
                'category' => $data['category'],
                'subject' => trim($data['subject']),
                'status' => PatientServiceConversation::STATUS_WAITING_ADMIN,
                'last_message_at' => now(),
            ]);

            $message = $conversation->messages()->create([
                'sender_id' => $patient->id,
                'body' => trim($data['message']),
            ]);

            return [$conversation, $message];
        });

        $this->prepareForBroadcast($conversation, $message);
        PatientServiceMessageSent::dispatch($message);
        $this->notificationService->sendFor($message);

        return $conversation;
    }

    public function sendMessage(
        PatientServiceConversation $conversation,
        User $sender,
        string $body,
    ): PatientServiceMessage {
        $message = DB::transaction(function () use ($conversation, $sender, $body): PatientServiceMessage {
            $lockedConversation = PatientServiceConversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            abort_if(
                $lockedConversation->status === PatientServiceConversation::STATUS_CLOSED,
                422,
                'Percakapan sudah diselesaikan. Buka kembali percakapan untuk mengirim pesan.',
            );

            $isAdmin = $sender->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA');

            if ($isAdmin && $lockedConversation->assigned_admin_id === null) {
                $lockedConversation->assigned_admin_id = $sender->id;
            }

            $lockedConversation->status = $isAdmin
                ? PatientServiceConversation::STATUS_WAITING_PATIENT
                : PatientServiceConversation::STATUS_WAITING_ADMIN;
            $lockedConversation->last_message_at = now();
            $lockedConversation->save();

            return $lockedConversation->messages()->create([
                'sender_id' => $sender->id,
                'body' => trim($body),
            ]);
        });

        $conversation->refresh();
        $this->prepareForBroadcast($conversation, $message);
        PatientServiceMessageSent::dispatch($message);
        $this->notificationService->sendFor($message);

        return $message;
    }

    public function updateStatus(
        PatientServiceConversation $conversation,
        User $actor,
        string $status,
    ): PatientServiceConversation {
        $conversation = DB::transaction(function () use ($conversation, $actor, $status): PatientServiceConversation {
            $lockedConversation = PatientServiceConversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            $lockedConversation->forceFill([
                'status' => $status === 'closed'
                    ? PatientServiceConversation::STATUS_CLOSED
                    : ($actor->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA')
                        ? PatientServiceConversation::STATUS_WAITING_PATIENT
                        : PatientServiceConversation::STATUS_WAITING_ADMIN),
                'closed_at' => $status === 'closed' ? now() : null,
            ])->save();

            return $lockedConversation;
        });

        $conversation->load(['patient:id,name', 'assignedAdmin:id,name', 'latestMessage']);
        PatientServiceConversationUpdated::dispatch($conversation);

        return $conversation;
    }

    public function messagePayload(PatientServiceMessage $message): array
    {
        $message->loadMissing('sender:id,name');
        if (
            ! array_key_exists('recipients_count', $message->getAttributes())
            || ! array_key_exists('readers_count', $message->getAttributes())
        ) {
            $message->loadCount(['recipients', 'readers']);
        }

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name ?? 'Pengguna',
            'sender_initials' => $message->sender?->initials() ?? 'PG',
            'body' => $message->body,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
            'time_label' => $message->created_at->translatedFormat('H:i'),
            'date_label' => $message->created_at->translatedFormat('d M Y'),
            'delivery_status' => $message->delivery_status,
            'delivery_status_label' => $message->delivery_status_label,
        ];
    }

    public function conversationPayload(PatientServiceConversation $conversation): array
    {
        $conversation->loadMissing(['patient:id,name', 'assignedAdmin:id,name', 'latestMessage']);

        return [
            'id' => $conversation->id,
            'patient_id' => $conversation->patient_id,
            'patient_name' => $conversation->patient?->name ?? 'Pasien',
            'patient_initials' => $conversation->patient?->initials() ?? 'PS',
            'assigned_admin_name' => $conversation->assignedAdmin?->name,
            'category' => $conversation->category,
            'category_label' => $conversation->category_label,
            'subject' => $conversation->subject,
            'status' => $conversation->status,
            'status_label' => $conversation->status_label,
            'last_message' => $conversation->latestMessage?->body,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'last_message_time' => $conversation->last_message_at?->diffForHumans(short: true),
            'closed_at' => $conversation->closed_at?->toIso8601String(),
        ];
    }

    private function prepareForBroadcast(
        PatientServiceConversation $conversation,
        PatientServiceMessage $message,
    ): void {
        $message->load('sender:id,name');
        $conversation->load(['patient:id,name', 'assignedAdmin:id,name', 'latestMessage']);
        $message->setRelation('conversation', $conversation);
    }
}
