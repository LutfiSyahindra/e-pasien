<?php

namespace App\Services\epasien\menu;

use App\Events\PatientServiceReceiptUpdated;
use App\Models\PatientServiceConversation;
use App\Models\PatientServiceMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientServiceReceiptService
{
    public function markDelivered(User $user, array|Collection $messageIds): int
    {
        $messageIds = collect($messageIds)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        if ($messageIds->isEmpty()) {
            return 0;
        }

        $messages = PatientServiceMessage::query()
            ->whereKey($messageIds)
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('recipients', fn ($query) => $query->whereKey($user->id))
            ->whereHas('conversation', fn ($query) => $query->visibleTo($user))
            ->get(['id', 'conversation_id']);

        if ($messages->isEmpty()) {
            return 0;
        }

        DB::table('patient_service_message_deliveries')->insertOrIgnore(
            $messages->map(fn (PatientServiceMessage $message): array => [
                'message_id' => $message->id,
                'user_id' => $user->id,
                'delivered_at' => now(),
            ])->all(),
        );

        $this->dispatchReceipts($messages, 'delivered', $user);

        return $messages->count();
    }

    public function markConversationRead(PatientServiceConversation $conversation, User $user): int
    {
        $messages = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readers', fn ($query) => $query->whereKey($user->id))
            ->get(['id', 'conversation_id']);

        if ($messages->isEmpty()) {
            return 0;
        }

        $readAt = now();

        DB::transaction(function () use ($messages, $user, $readAt): void {
            DB::table('patient_service_message_deliveries')->insertOrIgnore(
                $messages->map(fn (PatientServiceMessage $message): array => [
                    'message_id' => $message->id,
                    'user_id' => $user->id,
                    'delivered_at' => $readAt,
                ])->all(),
            );
            DB::table('patient_service_message_reads')->insertOrIgnore(
                $messages->map(fn (PatientServiceMessage $message): array => [
                    'message_id' => $message->id,
                    'user_id' => $user->id,
                    'read_at' => $readAt,
                ])->all(),
            );
            PatientServiceMessage::query()
                ->whereKey($messages->pluck('id'))
                ->whereNull('read_at')
                ->update(['read_at' => $readAt]);
        });

        $this->dispatchReceipts($messages, 'read', $user);

        return $messages->count();
    }

    private function dispatchReceipts(Collection $messages, string $status, User $user): void
    {
        $messages->groupBy('conversation_id')
            ->each(function (Collection $conversationMessages, int|string $conversationId) use ($status, $user): void {
                PatientServiceReceiptUpdated::dispatch(
                    (int) $conversationId,
                    $conversationMessages->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                    $status,
                    $user->id,
                );
            });
    }
}
