<?php

use App\Core\Assets\Contracts\AssetAccessResolver;
use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetReference;
use App\Core\Assets\Services\AssetGatekeeper;
use App\Core\Assets\Services\AssetReferencerRegistry;
use App\Core\Identity\Models\User;

/**
 * Grants only when the reference id matches the configured allowlist.
 */
class FakeAllowlistResolver implements AssetAccessResolver
{
    /** @var array<int, string> */
    public static array $allowedReferencerIds = [];

    public function canView(User $user, Asset $asset, AssetReference $reference): bool
    {
        return in_array((string) $reference->referencer_id, self::$allowedReferencerIds, true);
    }

    public function canDownload(User $user, Asset $asset, AssetReference $reference): bool
    {
        return $this->canView($user, $asset, $reference);
    }

    public function canShare(User $user, Asset $asset, AssetReference $reference): bool
    {
        return false;
    }
}

beforeEach(function (): void {
    FakeAllowlistResolver::$allowedReferencerIds = [];

    $this->registry = app(AssetReferencerRegistry::class);
    $this->gatekeeper = app(AssetGatekeeper::class);
    $this->user = User::factory()->create();
});

it('denies access when the asset has no references', function (): void {
    $asset = Asset::factory()->create();

    expect($this->gatekeeper->canView($this->user, $asset))->toBeFalse()
        ->and($this->gatekeeper->canDownload($this->user, $asset))->toBeFalse();
});

it('denies access when the referencer type has no registered resolver', function (): void {
    $asset = Asset::factory()->create();

    AssetReference::query()->create([
        'asset_id' => $asset->id,
        'referencer_type' => 'never-registered',
        'referencer_id' => 'abc',
        'role' => 'primary',
    ]);

    expect($this->gatekeeper->canView($this->user, $asset))->toBeFalse();
});

it('grants access when a registered resolver allows the reference', function (): void {
    $this->registry->register('fake-domain', FakeAllowlistResolver::class);
    FakeAllowlistResolver::$allowedReferencerIds = ['allowed-record'];

    $asset = Asset::factory()->create();

    AssetReference::query()->create([
        'asset_id' => $asset->id,
        'referencer_type' => 'fake-domain',
        'referencer_id' => 'allowed-record',
        'role' => 'primary',
    ]);

    expect($this->gatekeeper->canView($this->user, $asset))->toBeTrue();
});

it('denies a shared blob when the user only fails every reference', function (): void {
    $this->registry->register('fake-domain', FakeAllowlistResolver::class);
    FakeAllowlistResolver::$allowedReferencerIds = ['some-other-record'];

    $asset = Asset::factory()->create();

    AssetReference::query()->create([
        'asset_id' => $asset->id,
        'referencer_type' => 'fake-domain',
        'referencer_id' => 'denied-record',
        'role' => 'primary',
    ]);

    expect($this->gatekeeper->canView($this->user, $asset))->toBeFalse();
});

it('grants a deduplicated blob when any one of its references allows access', function (): void {
    $this->registry->register('fake-domain', FakeAllowlistResolver::class);
    FakeAllowlistResolver::$allowedReferencerIds = ['second-record'];

    $asset = Asset::factory()->create();

    AssetReference::query()->create([
        'asset_id' => $asset->id,
        'referencer_type' => 'fake-domain',
        'referencer_id' => 'first-record',
        'role' => 'primary',
    ]);

    AssetReference::query()->create([
        'asset_id' => $asset->id,
        'referencer_type' => 'fake-domain',
        'referencer_id' => 'second-record',
        'role' => 'attachment',
    ]);

    expect($this->gatekeeper->canView($this->user, $asset))->toBeTrue();
});

it('evaluates abilities independently', function (): void {
    $this->registry->register('fake-domain', FakeAllowlistResolver::class);
    FakeAllowlistResolver::$allowedReferencerIds = ['allowed-record'];

    $asset = Asset::factory()->create();

    AssetReference::query()->create([
        'asset_id' => $asset->id,
        'referencer_type' => 'fake-domain',
        'referencer_id' => 'allowed-record',
        'role' => 'primary',
    ]);

    expect($this->gatekeeper->canDownload($this->user, $asset))->toBeTrue()
        ->and($this->gatekeeper->canShare($this->user, $asset))->toBeFalse();
});

it('rejects resolvers that do not implement the contract', function (): void {
    $this->registry->register('bad', stdClass::class);
})->throws(InvalidArgumentException::class);
