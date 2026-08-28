<?php

namespace App\Domains\RFIs\Mail;

use App\Domains\RFIs\Models\RFI;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FormalRfiMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RFI $rfi,
        /**
         * @var array<int, string>
         */
        public array $recipients,
        public ?string $coverMessage = null,
        public ?string $customSubject = null,
    ) {}

    public function build(): self
    {
        return $this->subject($this->customSubject ?? $this->defaultSubject())
            ->view('rfis::emails.formal-rfi')
            ->with([
                'rfi' => $this->rfi,
                'recipients' => $this->recipients,
                'coverMessage' => $this->coverMessage,
            ]);
    }

    private function defaultSubject(): string
    {
        return sprintf(
            'RFI %s-%s: %s',
            (string) ($this->rfi->project?->project_number ?? 'PROJECT'),
            (string) $this->rfi->number,
            (string) $this->rfi->subject
        );
    }
}
