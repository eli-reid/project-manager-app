<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sets company_email from username during user creation', function () {
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.auto_create_emails', 'false');

    $user = User::factory()->create([
        'username' => 'jane',
        'company_email' => null,
    ]);

    expect($user->fresh()->company_email)->toBe('jane@example.test');
});

it('provisions cpanel mailbox when auto-create is enabled', function () {
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.auto_create_emails', 'true');
    Settings::set('cpanel.auto_delete_emails', 'true');
    Settings::set('cpanel.default_email_quota', '250');
    Settings::set('cpanel.verify_ssl', 'true');

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
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.auto_create_emails', 'false');
    Settings::set('cpanel.auto_delete_emails', 'true');
    Settings::set('cpanel.verify_ssl', 'true');

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
