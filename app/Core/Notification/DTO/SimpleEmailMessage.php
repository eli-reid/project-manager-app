class SimpleEmailMessage implements EmailMessage
{
    public function __construct(
        protected string $to,
        protected string $subject,
        protected string $body,
        protected ?string $from = null,
        protected array $cc = [],
        protected array $bcc = [],
        protected array $attachments = [],
    ) {}

    public function to(): string { return $this->to; }
    public function subject(): string { return $this->subject; }
    public function body(): string { return $this->body; }
    public function from(): ?string { return $this->from; }
    public function cc(): array { return $this->cc; }
    public function bcc(): array { return $this->bcc; }
    public function attachments(): array { return $this->attachments; }
}