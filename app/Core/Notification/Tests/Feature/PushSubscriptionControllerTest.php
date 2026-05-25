<?php

use App\Core\Identity\Models\User;
use NotificationChannels\WebPush\PushSubscription;

it('stores a push subscription for an authenticated user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->postJson(route('push-subscriptions.store'), [
        'endpoint' => 'https://example.test/push/subscription/abc123',
        'key' => 'public-key',
        'token' => 'auth-token',
        'encoding' => 'aes128gcm',
    ]);

    $response->assertOk()
        ->assertJson(['subscribed' => true]);

    expect(PushSubscription::query()
        ->where('subscribable_type', $user->getMorphClass())
        ->where('subscribable_id', $user->getKey())
        ->where('endpoint', 'https://example.test/push/subscription/abc123')
        ->exists())->toBeTrue();
});

it('deletes a push subscription for an authenticated user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $user->updatePushSubscription(
        'https://example.test/push/subscription/delete-me',
        'public-key',
        'auth-token',
        'aes128gcm',
    );

    $response = $this->actingAs($user)->deleteJson(route('push-subscriptions.destroy'), [
        'endpoint' => 'https://example.test/push/subscription/delete-me',
    ]);

    $response->assertOk()
        ->assertJson(['subscribed' => false]);

    expect(PushSubscription::query()
        ->where('subscribable_type', $user->getMorphClass())
        ->where('subscribable_id', $user->getKey())
        ->where('endpoint', 'https://example.test/push/subscription/delete-me')
        ->exists())->toBeFalse();
});

it('requires authentication to manage push subscriptions', function (): void {
    $this->postJson(route('push-subscriptions.store'), [
        'endpoint' => 'https://example.test/push/subscription/guest',
    ])->assertUnauthorized();

    $this->deleteJson(route('push-subscriptions.destroy'), [
        'endpoint' => 'https://example.test/push/subscription/guest',
    ])->assertUnauthorized();
});
