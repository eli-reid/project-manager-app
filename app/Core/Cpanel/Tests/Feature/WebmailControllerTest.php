<?php

use App\Core\User\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

it('redirects authenticated users to webmail session url', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => 'example.test',
        'port' => 2083,
        'webmail_port' => 2096,
        'verify_ssl' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Session/create_webmail_session_for_self' => Http::response([
            'status' => 1,
            'data' => [
                'url' => 'https://webmail.example.test/session-token',
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'username' => 'john',
        'company_email' => 'john@example.test',
    ]);

    actingAs($user)
        ->get(route('webmail.redirect'))
        ->assertRedirect('https://webmail.example.test/session-token');
});

it('returns to dashboard with an error when user has no company email', function () {
    config()->set('services.cpanel', [
        'url' => 'https://cpanel.example.test',
        'username' => 'root',
        'api_token' => 'token-123',
        'domain' => '',
    ]);

    $user = User::factory()->create([
        'company_email' => null,
        'username' => 'no-company-email',
    ]);

    actingAs($user)
        ->get(route('webmail.redirect'))
        ->assertRedirect(route('dashboard', absolute: false))
        ->assertSessionHas('error', 'No company email is configured for your account.');
});
