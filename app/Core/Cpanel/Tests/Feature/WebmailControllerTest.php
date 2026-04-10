<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

it('redirects authenticated users to webmail session url', function () {
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');
    Settings::set('cpanel.port', 2083);
    Settings::set('cpanel.webmail_port', 2096);

    Http::preventStrayRequests();
    Http::fake([
        'https://cpanel.example.test:2083/execute/Session/create_webmail_session_for_mail_user*' => Http::response([
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
    Settings::set('cpanel.url', null);
    Settings::set('cpanel.username', null);
    Settings::set('cpanel.api_token', null);
    Settings::set('cpanel.domain', null);

    $user = User::factory()->create([
        'company_email' => null,
        'username' => '',
    ]);

    actingAs($user)
        ->get(route('webmail.redirect'))
        ->assertRedirect(route('dashboard', absolute: false))
        ->assertSessionHas('error', 'No company email is configured for your account.');
});

it('shows the user webmail link on the dashboard', function () {
    Settings::set('cpanel.url', 'https://cpanel.example.test');
    Settings::set('cpanel.username', 'root');
    Settings::set('cpanel.api_token', 'token-123');
    Settings::set('cpanel.domain', 'example.test');

    $user = User::factory()->create([
        'username' => 'john',
        'company_email' => 'john@example.test',
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Webmail')
        ->assertSee(route('webmail.redirect', absolute: false), false);
});

it('hides the user webmail link when cpanel is not configured', function () {
    Settings::set('cpanel.url', null);
    Settings::set('cpanel.username', null);
    Settings::set('cpanel.api_token', null);
    Settings::set('cpanel.domain', null);

    $user = User::factory()->create([
        'username' => 'john',
        'company_email' => 'john@example.test',
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Webmail')
        ->assertDontSee(route('webmail.redirect', absolute: false), false);
});
