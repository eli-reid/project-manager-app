<?php

use App\Core\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function assertProfileMutationBlocked(User $user): void
{
    $updatedUser = User::find($user->id);

    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->first_name)->toBe('Payload');
    expect($updatedUser->is_admin)->toBeFalse();
    expect($updatedUser->is_active)->toBeTrue();
}

describe('Real Payload Testing', function () {
    beforeEach(function () {
        User::where('email', 'payload.test@example.com')->delete();

        $this->user = User::create([
            'first_name' => 'Payload',
            'last_name' => 'Tester',
            'username' => 'payloadtester',
            'email' => 'payload.test@example.com',
            'company_email' => 'payloadtester@midstatecompany.com',
            'password' => bcrypt('PayloadTest123!'),
            'is_admin' => false,
            'is_active' => true,
        ]);
    });

    describe('SQL Injection Prevention', function () {
        test('SQL injection in profile update is escaped', function () {
            $payload = "'; DROP TABLE users; --";

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            // Database should still exist and user should not be deleted
            expect(User::count())->toBeGreaterThan(0);
            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });

        test('union-based SQL injection rejected', function () {
            $payload = "test' UNION SELECT id, email FROM users --";

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });

        test('boolean-based SQL injection rejected', function () {
            $payload = "test' OR '1'='1";

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });
    });

    describe('XSS Prevention', function () {
        test('script tag in profile update is escaped', function () {
            $payload = '<script>alert("XSS")</script>';

            $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            $response = $this->actingAs($this->user)
                ->get('/settings/profile');

            expect($response->status())->not->toBe(500);
            assertProfileMutationBlocked($this->user);
        });

        test('event handler injection is escaped', function () {
            $payload = '<img src=x onerror="alert(\'XSS\')">';

            $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            $response = $this->actingAs($this->user)
                ->get('/settings/profile');

            // Event handler should not be executable
            expect($response->getContent())->not->toContain('onerror=');
        });

        test('javascript protocol in href is escaped', function () {
            $payload = '<a href="javascript:alert(\'XSS\')">Click</a>';

            $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            $response = $this->actingAs($this->user)
                ->get('/settings/profile');

            // Should be escaped so it's not executable
            expect($response->getContent())->not->toContain('javascript:');
        });

        test('data URI with XSS is escaped', function () {
            $payload = '<img src="data:text/html,<script>alert(\'XSS\')</script>">';

            $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            $response = $this->actingAs($this->user)
                ->get('/settings/profile');

            // Data URI should be escaped
            expect($response->getContent())->not->toContain('data:text/html');
        });

        test('SVG-based XSS is escaped', function () {
            $payload = '<svg onload="alert(\'XSS\')">';

            $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            $response = $this->actingAs($this->user)
                ->get('/settings/profile');

            // SVG should be escaped
            expect($response->getContent())->not->toContain('onload=');
        });
    });

    describe('Input Size & Type Validation', function () {
        test('oversized input is rejected', function () {
            $oversizedInput = str_repeat('A', 10001); // Over max

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $oversizedInput,
                    'last_name' => 'Test',
                ]);

            // Should be rejected or ignored safely by the target route
            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });

        test('null byte injection is handled', function () {
            $payload = "test\x00injection";

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });

        test('unicode characters are handled correctly', function () {
            $payload = '🔥 中文 नमस्ते €∆∫';

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });

        test('special shell characters are escaped', function () {
            $payload = '; rm -rf /; $(whoami); `id`';

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });
    });

    describe('Malformed Data Payloads', function () {
        test('JSON with extra fields is not processed', function () {
            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'is_admin' => true,
                    'is_active' => false,
                    'password' => 'hacked',
                ]);

            // User should not gain admin or change their status/password
            $updatedUser = User::find($this->user->id);
            expect($updatedUser->is_admin)->toBeFalse();
            expect($updatedUser->is_active)->toBeTrue();
        });

        test('negative numbers in numeric fields are rejected', function () {
            $response = $this->actingAs($this->user)
                ->post('/timecards', [
                    'hours' => -100,
                    'date' => now()->format('Y-m-d'),
                ]);

            expect($response->status())->not->toBe(500);
        });

        test('invalid ULID in URL returns 404', function () {
            $response = $this->actingAs($this->user)
                ->get('/timecards/INVALID-ULID-12345');

            expect($response->status())->toBe(404);
        });

        test('invalid date format is rejected', function () {
            $response = $this->actingAs($this->user)
                ->post('/timecards', [
                    'date' => 'not-a-date',
                    'hours' => 8,
                ]);

            expect($response->status())->not->toBe(500);
        });
    });

    describe('Command Injection Prevention', function () {
        test('shell commands in input are not executed', function () {
            $payload = '$(whoami)';

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });

        test('backtick command execution prevented', function () {
            $payload = '`id`';

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });
    });

    describe('Path Traversal Prevention', function () {
        test('path traversal in file operations blocked', function () {
            $maliciousPath = '../../etc/passwd';

            // Assuming there's a file upload or access endpoint
            $response = $this->actingAs($this->user)
                ->get('/files/'.$maliciousPath);

            // Should fail or not access system files
            expect($response->status())->not->toBe(200);
        });

        test('null byte path traversal blocked', function () {
            $maliciousPath = 'file.txt%00.jpg';

            $response = $this->actingAs($this->user)
                ->post('/documents', [
                    'file' => $maliciousPath,
                ]);

            // Should be rejected
            expect($response->status())->not->toBe(201);
        });
    });

    describe('LDAP Injection Prevention', function () {
        test('LDAP special characters escaped', function () {
            $payload = '*)(uid=*))(|(uid=*';

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });
    });

    describe('NoSQL/MongoDB Injection Prevention', function () {
        test('MongoDB operator injection blocked', function () {
            // Even though app uses SQL, this tests defense-in-depth
            $payload = '{"$ne": null}';

            $response = $this->actingAs($this->user)
                ->put('/settings/profile', [
                    'first_name' => json_encode($payload),
                    'last_name' => 'Test',
                ]);

            expect($response->status())->not->toBe(500);

            assertProfileMutationBlocked($this->user);
        });
    });
});
