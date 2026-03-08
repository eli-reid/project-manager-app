<?php

use App\Core\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sets company_email from username during user creation', function () {
    config()->set('services.cpanel', [
        'domain' => 'example.test',
        'auto_create_emails' => false,
    ]);

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => null,
    ]);

    expect($user->fresh()->company_email)->toBe('jane@example.test');
});

it('provisions cpanel mailbox when auto-create is enabled', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'auto_create_emails' => true,
        'auto_delete_emails' => true,
        'default_email_quota' => 250,
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/add_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    User::factory()->create([
        'username' => 'jane',
        'company_email' => null,
    ]);

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/add_pop'
            && ($data['email'] ?? null) === 'jane';
    });
});

it('deletes cpanel mailbox when user is deleted and auto-delete is enabled', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'auto_create_emails' => false,
        'auto_delete_emails' => true,
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Email/delete_pop' => Http::response([
            'status' => 1,
            'data' => [],
        ]),
    ]);

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => 'jane@example.test',
    ]);

    $user->delete();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://cpanel.example.test:2083/execute/Email/delete_pop'
            && ($data['email'] ?? null) === 'jane'
            && ($data['domain'] ?? null) === 'example.test';
    });
});
