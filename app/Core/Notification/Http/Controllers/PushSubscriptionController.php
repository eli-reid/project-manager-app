<?php

namespace App\Core\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'key' => ['nullable', 'string'],
            'token' => ['nullable', 'string'],
            'encoding' => ['nullable', 'string'],
        ]);

        $user->updatePushSubscription(
            $validated['endpoint'],
            $validated['key'] ?? null,
            $validated['token'] ?? null,
            $validated['encoding'] ?? null,
        );

        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $user->deletePushSubscription($validated['endpoint']);

        return response()->json(['subscribed' => false]);
    }
}
