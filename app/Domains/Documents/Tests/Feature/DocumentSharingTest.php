<?php

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentShare;
use App\Domains\Documents\Services\DocumentShareService;

beforeEach(function (): void {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);
});

describe('share creation', function (): void {
    it('allows user to create a share with permission', function (): void {
        $document = Document::factory()->create();
        $document->ownerUsers()->sync([$this->user->id]);

        $this->actingAs($this->user);

        $share = app(DocumentShareService::class)->createShare($document, $this->user, [
            'password' => 'test123',
            'expires_at' => now()->addDays(7),
            'max_downloads' => 10,
            'access_notes' => 'Test share',
        ]);

        expect($share->document_id)->toBe($document->id);
        expect($share->created_by_id)->toBe($this->user->id);
        expect($share->verifyPassword('test123'))->toBeTrue();
        expect($share->expires_at)->not->toBeNull();
        expect($share->max_downloads)->toBe(10);
        expect($share->access_notes)->toBe('Test share');
    });

    it('generates unique share tokens', function (): void {
        $document = Document::factory()->create();
        $service = app(DocumentShareService::class);

        $share1 = $service->createShare($document, $this->user);
        $share2 = $service->createShare($document, $this->user);

        expect($share1->share_token)->not->toBe($share2->share_token);
    });
});

describe('share validation', function (): void {
    it('validates share is valid when active and not expired', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        expect($share->isValid())->toBeTrue();
    });

    it('invalidates expired shares', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'expires_at' => now()->subDay(),
        ]);

        expect($share->isValid())->toBeFalse();
    });

    it('invalidates disabled shares', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'is_active' => false,
        ]);

        expect($share->isValid())->toBeFalse();
    });

    it('validates password correctly', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'share_password' => hash('sha256', 'test123'),
        ]);

        expect($share->verifyPassword('test123'))->toBeTrue();
        expect($share->verifyPassword('wrong'))->toBeFalse();
    });

    it('detects password requirement', function (): void {
        $protected = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'share_password' => hash('sha256', 'test123'),
        ]);
        $public = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
        ]);

        expect($protected->requiresPassword())->toBeTrue();
        expect($public->requiresPassword())->toBeFalse();
    });
});

describe('share management', function (): void {
    it('toggles share active status', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'is_active' => true,
        ]);
        $service = app(DocumentShareService::class);

        $updated = $service->toggleShare($share, false);

        expect($updated->is_active)->toBeFalse();
    });

    it('finds share by token', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'is_active' => true,
        ]);
        $service = app(DocumentShareService::class);

        $found = $service->findShareByToken($share->share_token);

        expect($found->id)->toBe($share->id);
    });

    it('returns null for invalid token', function (): void {
        $service = app(DocumentShareService::class);

        $found = $service->findShareByToken('invalid-token-'.str()->random(20));

        expect($found)->toBeNull();
    });

    it('deletes a share', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
        ]);
        $service = app(DocumentShareService::class);

        $service->deleteShare($share);

        expect(DocumentShare::find($share->id))->toBeNull();
    });
});

describe('share routes', function (): void {
    it('users can access public shared documents with valid token', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'is_active' => true,
        ]);

        $response = $this->get(route('share.view', $share->share_token));

        $response->assertOk();
        $response->assertViewHas('share');
    });

    it('rejects expired shares', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get(route('share.view', $share->share_token));

        $response->assertForbidden();
    });

    it('verifies password correctly', function (): void {
        $share = DocumentShare::create([
            'document_id' => Document::factory()->create()->id,
            'created_by_id' => $this->user->id,
            'share_token' => DocumentShare::generateShareToken(),
            'share_password' => hash('sha256', 'test123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('share.verify-password', $share->share_token), [
            'password' => 'test123',
        ]);

        expect(session("share.{$share->share_token}.verified"))->toBeTrue();
        $response->assertRedirect();
    });
});
