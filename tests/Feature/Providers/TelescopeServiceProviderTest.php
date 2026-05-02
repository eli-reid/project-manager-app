<?php

use App\Core\Identity\Models\User;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function telescopeProvider(): object
{
    return new class(app()) extends TelescopeServiceProvider
    {
        public function canAccessForTest(mixed $user): bool
        {
            return $this->canAccessTelescope($user);
        }

        public function shouldRecordForTest(IncomingEntry $entry, bool $isLocal): bool
        {
            return $this->shouldRecordEntry($entry, $isLocal);
        }
    };
}

it('allows admins to access telescope', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    expect(Gate::forUser($admin)->allows('viewTelescope'))->toBeTrue();
});

it('denies non-admins telescope access', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    expect(Gate::forUser($user)->allows('viewTelescope'))->toBeFalse();
});

it('records production telescope entries for admins', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $provider = telescopeProvider();

    $entry = Mockery::mock(IncomingEntry::class);
    $entry->shouldReceive('isReportableException')->never();
    $entry->shouldReceive('isFailedRequest')->never();
    $entry->shouldReceive('isFailedJob')->never();
    $entry->shouldReceive('isScheduledTask')->never();
    $entry->shouldReceive('hasMonitoredTag')->never();

    $this->actingAs($admin);

    expect($provider->canAccessForTest($admin))->toBeTrue()
        ->and($provider->shouldRecordForTest($entry, false))->toBeTrue();
});

it('keeps production telescope filtering limited for non-admins', function (): void {
    $provider = telescopeProvider();

    $entry = Mockery::mock(IncomingEntry::class);
    $entry->shouldReceive('isReportableException')->once()->andReturnFalse();
    $entry->shouldReceive('isFailedRequest')->once()->andReturnFalse();
    $entry->shouldReceive('isFailedJob')->once()->andReturnFalse();
    $entry->shouldReceive('isScheduledTask')->once()->andReturnFalse();
    $entry->shouldReceive('hasMonitoredTag')->once()->andReturnFalse();

    expect($provider->shouldRecordForTest($entry, false))->toBeFalse();
});

afterEach(function (): void {
    Mockery::close();
});
