<?php

use App\Models\PatientServiceConversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('patient-service.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = PatientServiceConversation::query()->find($conversationId);

    return $conversation?->isVisibleTo($user) ?? false;
});

Broadcast::channel('patient-service.admin', function ($user) {
    return $user->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA');
});
