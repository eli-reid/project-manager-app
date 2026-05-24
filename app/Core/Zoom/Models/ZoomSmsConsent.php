<?php

namespace App\Core\Zoom\Models;

use App\Core\Zoom\Enums\SmsConsentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperZoomSmsConsent
 */
class ZoomSmsConsent extends Model
{
    use HasUlids;

    protected $fillable = [
        'phone_number',
        'status',
        'consent_requested_at',
        'consented_at',
        'declined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SmsConsentStatus::class,
            'consent_requested_at' => 'datetime',
            'consented_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function isOptedIn(): bool
    {
        return $this->status === SmsConsentStatus::OptedIn;
    }

    public function isOptedOut(): bool
    {
        return $this->status === SmsConsentStatus::OptedOut;
    }

    public function isPending(): bool
    {
        return $this->status === SmsConsentStatus::Pending;
    }
}
