<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Menu\StorePatientServiceConversationRequest;
use App\Http\Requests\Epasien\Menu\StorePatientServiceMessageRequest;
use App\Models\PatientServiceConversation;
use App\Models\PatientServiceMessage;
use App\Notifications\PatientServiceMessageNotification;
use App\Services\epasien\menu\PatientServiceChatService;
use App\Services\epasien\menu\PatientServiceReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientServiceController extends Controller
{
    public function __construct(
        private readonly PatientServiceChatService $chatService,
        private readonly PatientServiceReceiptService $receiptService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $canManage = $user->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA');
        $filters = $request->validate([
            'conversation' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'open', 'waiting_admin', 'waiting_patient', 'closed'])],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? 'all';

        $conversationQuery = PatientServiceConversation::query()
            ->visibleTo($user)
            ->with([
                'patient:id,name',
                'latestMessage',
            ])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('readers', fn ($query) => $query->whereKey($user->id)),
            ]);

        if ($canManage) {
            $conversationQuery
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('subject', 'like', '%'.$search.'%')
                            ->orWhereHas('patient', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
                    });
                })
                ->when($status === 'open', fn ($query) => $query->where('status', '!=', PatientServiceConversation::STATUS_CLOSED))
                ->when(in_array($status, [
                    PatientServiceConversation::STATUS_WAITING_ADMIN,
                    PatientServiceConversation::STATUS_WAITING_PATIENT,
                    PatientServiceConversation::STATUS_CLOSED,
                ], true), fn ($query) => $query->where('status', $status));
        }

        $conversations = $conversationQuery
            ->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
            ->orderByDesc('last_message_at')
            ->limit(60)
            ->get();

        $activeConversation = null;
        $requestedConversationId = (int) ($filters['conversation'] ?? 0);

        if ($requestedConversationId > 0) {
            $activeConversation = PatientServiceConversation::query()
                ->visibleTo($user)
                ->with(['patient:id,name', 'assignedAdmin:id,name'])
                ->findOrFail($requestedConversationId);
        } elseif ($conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        $messages = collect();
        if ($activeConversation) {
            $messages = $activeConversation->messages()
                ->with('sender:id,name')
                ->withCount(['recipients', 'readers'])
                ->latest('id')
                ->limit(150)
                ->get()
                ->reverse()
                ->values();
        }

        return view('e-pasien.menu.patientService.index', [
            'canManage' => $canManage,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function navbar(Request $request): JsonResponse
    {
        $user = $request->user();
        $canManage = $user->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA');
        $conversations = PatientServiceConversation::query()
            ->visibleTo($user)
            ->with([
                'patient:id,name',
                'latestMessage',
            ])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('readers', fn ($query) => $query->whereKey($user->id)),
            ])
            ->orderByDesc('last_message_at')
            ->limit(10)
            ->get();

        $unreadCount = PatientServiceMessage::query()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readers', fn ($query) => $query->whereKey($user->id))
            ->whereHas('conversation', fn ($query) => $query->visibleTo($user))
            ->count();
        $pendingDeliveryMessageIds = PatientServiceMessage::query()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('recipients', fn ($query) => $query->whereKey($user->id))
            ->whereHas('conversation', fn ($query) => $query->visibleTo($user))
            ->latest('id')
            ->limit(100)
            ->pluck('id');

        return response()->json([
            'unread_count' => $unreadCount,
            'pending_delivery_message_ids' => $pendingDeliveryMessageIds,
            'conversations' => $conversations->map(fn (PatientServiceConversation $conversation): array => [
                'id' => $conversation->id,
                'name' => $canManage
                    ? ($conversation->patient?->name ?? 'Pasien')
                    : 'Tim Pasien Service',
                'initials' => $canManage
                    ? ($conversation->patient?->initials() ?? 'PS')
                    : 'PS',
                'subject' => $conversation->subject,
                'preview' => $conversation->latestMessage
                    ? (($conversation->latestMessage->sender_id === $user->id ? 'Anda: ' : '').$conversation->latestMessage->body)
                    : 'Belum ada pesan',
                'status' => $conversation->status,
                'status_label' => $conversation->status_label,
                'unread_count' => (int) $conversation->unread_count,
                'time_label' => $conversation->last_message_at?->diffForHumans(short: true),
                'url' => route('patientService.index', ['conversation' => $conversation->id], absolute: false),
            ])->values(),
        ]);
    }

    public function storeConversation(StorePatientServiceConversationRequest $request): JsonResponse
    {
        $conversation = $this->chatService->createConversation($request->user(), $request->validated());

        return response()->json([
            'message' => 'Percakapan berhasil dibuat.',
            'conversation' => $this->chatService->conversationPayload($conversation),
            'redirect_url' => route('patientService.index', ['conversation' => $conversation->id]),
        ], 201);
    }

    public function messages(Request $request, PatientServiceConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->withCount(['recipients', 'readers'])
            ->where('id', '>', (int) ($validated['after_id'] ?? 0))
            ->oldest('id')
            ->limit(100)
            ->get()
            ->map(fn ($message): array => $this->chatService->messagePayload($message));

        return response()->json([
            'messages' => $messages,
            'conversation' => $this->chatService->conversationPayload($conversation),
            'receipt_statuses' => $conversation->messages()
                ->where('sender_id', $request->user()->id)
                ->withCount(['recipients', 'readers'])
                ->latest('id')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (PatientServiceMessage $message): array => [
                    $message->id => $message->delivery_status,
                ]),
        ]);
    }

    public function markDelivered(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message_ids' => ['required', 'array', 'max:100'],
            'message_ids.*' => ['integer', 'distinct', 'min:1'],
        ]);

        return response()->json([
            'updated' => $this->receiptService->markDelivered(
                $request->user(),
                $validated['message_ids'],
            ),
        ]);
    }

    public function storeMessage(
        StorePatientServiceMessageRequest $request,
        PatientServiceConversation $conversation,
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation);
        $message = $this->chatService->sendMessage(
            $conversation,
            $request->user(),
            $request->validated('message'),
        );

        return response()->json([
            'message' => $this->chatService->messagePayload($message),
            'conversation' => $this->chatService->conversationPayload($conversation),
        ], 201);
    }

    public function markRead(Request $request, PatientServiceConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $updated = $this->receiptService->markConversationRead($conversation, $request->user());

        $now = now();
        $request->user()->unreadNotifications()
            ->where('type', PatientServiceMessageNotification::class)
            ->where('data->conversation_id', $conversation->id)
            ->update([
                'read_at' => $now,
                'updated_at' => $now,
            ]);

        return response()->json([
            'updated' => $updated,
            'notification_unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function updateStatus(Request $request, PatientServiceConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'closed'])],
        ]);

        $conversation = $this->chatService->updateStatus(
            $conversation,
            $request->user(),
            $validated['status'],
        );

        return response()->json([
            'message' => $validated['status'] === 'closed'
                ? 'Percakapan ditandai selesai.'
                : 'Percakapan dibuka kembali.',
            'conversation' => $this->chatService->conversationPayload($conversation),
        ]);
    }

    private function authorizeConversation(Request $request, PatientServiceConversation $conversation): void
    {
        abort_unless($conversation->isVisibleTo($request->user()), 403);
    }
}
