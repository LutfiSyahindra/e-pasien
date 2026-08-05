<?php

namespace App\Services\epasien\menu;

use App\Models\PatientServiceMessage;
use App\Models\User;
use App\Notifications\PatientServiceMessageNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class PatientServiceNotificationService
{
    public function sendFor(PatientServiceMessage $message): void
    {
        $message->loadMissing([
            'sender:id,name',
            'conversation.patient:id,name,status',
        ]);

        $conversation = $message->conversation;
        $sender = $message->sender;
        if (! $conversation || ! $sender) {
            return;
        }

        $senderIsTeamMember = $sender->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA');
        $recipients = $senderIsTeamMember
            ? collect([$conversation->patient])->filter()
            : $this->teamRecipients($sender);

        if ($recipients->isEmpty()) {
            return;
        }

        $preview = Str::limit(preg_replace('/\s+/', ' ', $message->body) ?: $message->body, 140);
        $title = $senderIsTeamMember
            ? 'Balasan dari Pasien Service'
            : 'Pesan Pasien Service dari '.$conversation->patient->name;

        Notification::send($recipients, new PatientServiceMessageNotification([
            'title' => $title,
            'body' => $conversation->subject.' — '.$preview,
            'push_title' => $senderIsTeamMember
                ? 'Balasan dari Pasien Service'
                : 'Pesan dari '.$conversation->patient->name,
            'push_body' => $preview,
            'url' => route('patientService.index', ['conversation' => $conversation->id], absolute: false),
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'subject' => $conversation->subject,
            'category_label' => $conversation->category_label,
        ]));
    }

    private function teamRecipients(User $sender): Collection
    {
        $roleNames = array_unique([
            config('access-control.super_admin_role', 'Super Admin'),
        ]);
        $permission = 'EPASIEN.MENU.PASIEN_SERVICE.KELOLA';

        return User::query()
            ->whereKeyNot($sender->id)
            ->where('status', true)
            ->where(function ($query) use ($roleNames, $permission): void {
                $query->whereHas('roles', function ($query) use ($roleNames, $permission): void {
                    $query->whereIn('name', $roleNames)
                        ->orWhereHas('permissions', fn ($query) => $query->where('name', $permission));
                })->orWhereHas('permissions', fn ($query) => $query->where('name', $permission));
            })
            ->get();
    }
}
