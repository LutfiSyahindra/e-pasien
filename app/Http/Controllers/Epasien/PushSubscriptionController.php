<?php

namespace App\Http\Controllers\Epasien;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushSubscriptionController extends Controller
{
    public const SESSION_ENDPOINT_KEY = 'epasien_push_subscription_endpoint';

    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'enabled' => filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')),
            'public_key' => config('webpush.vapid.public_key'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')), 503);

        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'content_encoding' => ['nullable', Rule::in(['aesgcm', 'aes128gcm'])],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['content_encoding'] ?? 'aes128gcm',
        );
        $request->session()->put(self::SESSION_ENDPOINT_KEY, $data['endpoint']);

        return response()->json(['message' => 'Notifikasi perangkat berhasil diaktifkan.'], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:500']]);
        $request->user()->deletePushSubscription($data['endpoint']);

        if ($request->session()->get(self::SESSION_ENDPOINT_KEY) === $data['endpoint']) {
            $request->session()->forget(self::SESSION_ENDPOINT_KEY);
        }

        return response()->json(['message' => 'Notifikasi perangkat dinonaktifkan.']);
    }
}
