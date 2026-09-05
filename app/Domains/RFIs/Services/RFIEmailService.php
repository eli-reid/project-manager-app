<?php

namespace App\Domains\RFIs\Services;

use App\Core\Identity\Models\User;
use App\Domains\RFIs\Mail\FormalRfiMailable;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Models\RFIEmailDelivery;
use Illuminate\Support\Facades\Mail;

class RFIEmailService
{
    /**
     * @param  array<int, string>  $recipients
     */
    public function sendFormalRfi(
        RFI $rfi,
        User $sentBy,
        array $recipients,
        ?string $coverMessage = null,
        ?string $subject = null,
    ): RFIEmailDelivery {
        $rfi->loadMissing(['project', 'requestedBy', 'answeredBy', 'documents']);

        Mail::to($recipients)->send(new FormalRfiMailable(
            rfi: $rfi,
            recipients: $recipients,
            coverMessage: $coverMessage,
            customSubject: $subject,
        ));

        return RFIEmailDelivery::query()->create([
            'rfi_id' => $rfi->id,
            'sent_by_id' => $sentBy->id,
            'recipients' => array_values($recipients),
            'subject' => $subject ?: $this->defaultSubject($rfi),
            'cover_message' => $coverMessage,
            'sent_at' => now(),
        ]);
    }

    private function defaultSubject(RFI $rfi): string
    {
        return sprintf(
            'RFI %s-%s: %s',
            (string) ($rfi->project?->project_number ?? 'PROJECT'),
            (string) $rfi->number,
            (string) $rfi->subject,
        );
    }
}
