<?php

namespace App\Core\Zoom\Enums;

enum SmsConsentStatus: string
{
    /** Consent request has been sent; awaiting reply. */
    case Pending = 'pending';

    /** Recipient has opted in; messages may be sent. */
    case OptedIn = 'opted_in';

    /** Recipient has opted out; no messages should be sent. */
    case OptedOut = 'opted_out';

    public function canReceiveMessages(): bool
    {
        return $this === self::OptedIn;
    }

    public function needsConsentRequest(): bool
    {
        return $this !== self::Pending && $this !== self::OptedIn && $this !== self::OptedOut;
    }
}
