<?php

namespace App\Http\Controllers\Epasien;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->select(['id', 'data', 'read_at', 'created_at'])
            ->latest()
            ->limit(15)
            ->get();
        $promotions = Promotion::query()
            ->whereKey($notifications->pluck('data.promotion_id')->filter()->unique())
            ->select(['id', 'category', 'title', 'image_path'])
            ->get()
            ->keyBy(fn (Promotion $promotion): string => (string) $promotion->getKey());

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications->map(fn ($notification): array => [
                'id' => $notification->id,
                'data' => $this->normalizeData($notification->data, $promotions),
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
                'time_label' => $notification->created_at->locale('id')->diffForHumans(),
            ]),
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['unread_count' => 0]);
    }

    private function normalizeData(array $data, Collection $promotions): array
    {
        if (($data['kind'] ?? null) !== 'promotion') {
            return $data;
        }

        $promotion = $promotions->get((string) ($data['promotion_id'] ?? ''));

        if (! $promotion) {
            return [
                ...$data,
                'image_url' => null,
                'url' => route('promotions.index', absolute: false),
            ];
        }

        return [
            ...$data,
            'category' => $promotion->category,
            'category_label' => $promotion->category_label,
            'title' => $promotion->category_label.': '.$promotion->title,
            'image_url' => $promotion->image_path ? $promotion->image_url : null,
            'url' => route('promotions.show', $promotion, absolute: false),
        ];
    }
}
