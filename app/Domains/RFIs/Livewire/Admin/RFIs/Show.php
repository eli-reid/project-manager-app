<?php

namespace App\Domains\RFIs\Livewire\Admin\RFIs;

use App\Core\Identity\Models\User;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Services\RFIEmailService;
use App\Domains\RFIs\Services\RFILifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('stock::livewire.layouts.stock-invoices-admin')]
#[Title('Review RFI')]
class Show extends Component
{
    use AuthorizesRequests;

    public RFI $rfi;

    public string $answer = '';

    public string $costImpact = '';

    public string $scheduleImpactDays = '';

    public bool $showEmailForm = false;

    public string $emailRecipients = '';

    public string $emailSubject = '';

    public string $emailMessage = '';

    public function mount(RFI $rfi): void
    {
        $this->authorize('view', $rfi);
        $this->rfi = $rfi->load([
            'project:id,name,project_number',
            'requestedBy',
            'answeredBy',
            'documents',
            'emailDeliveries.sentBy:id,first_name,last_name',
        ]);

        $this->emailSubject = sprintf(
            'RFI %s-%s: %s',
            (string) ($this->rfi->project?->project_number ?? 'PROJECT'),
            (string) $this->rfi->number,
            (string) $this->rfi->subject
        );
    }

    public function answer(RFILifecycleService $service): void
    {
        $this->authorize('answer', $this->rfi);

        $this->validate([
            'answer' => ['required', 'string', 'min:5'],
            'costImpact' => ['nullable', 'numeric', 'min:0'],
            'scheduleImpactDays' => ['nullable', 'integer'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $service->answer($this->rfi, $user, [
            'answer' => $this->answer,
            'cost_impact' => $this->costImpact !== '' ? $this->costImpact : null,
            'schedule_impact_days' => $this->scheduleImpactDays !== '' ? (int) $this->scheduleImpactDays : null,
        ]);

        $this->rfi->refresh();
        $this->answer = '';
        $this->costImpact = '';
        $this->scheduleImpactDays = '';

        $this->dispatch('notify', message: 'RFI answered successfully.');
    }

    public function close(RFILifecycleService $service): void
    {
        $this->authorize('close', $this->rfi);

        $service->close($this->rfi);

        $this->rfi->refresh();
    }

    public function cancel(RFILifecycleService $service): void
    {
        $this->authorize('cancel', $this->rfi);

        $service->cancel($this->rfi);

        $this->rfi->refresh();
    }

    public function toggleEmailForm(): void
    {
        $this->authorize('email', $this->rfi);

        $this->showEmailForm = ! $this->showEmailForm;
    }

    public function sendEmail(RFIEmailService $service): void
    {
        $this->authorize('email', $this->rfi);

        $this->validate([
            'emailRecipients' => ['required', 'string', 'max:2000'],
            'emailSubject' => ['required', 'string', 'max:255'],
            'emailMessage' => ['nullable', 'string', 'max:4000'],
        ]);

        $recipients = $this->parseRecipients();

        /** @var User $user */
        $user = Auth::user();

        $service->sendFormalRfi(
            $this->rfi,
            $user,
            $recipients,
            $this->emailMessage !== '' ? $this->emailMessage : null,
            $this->emailSubject,
        );

        $this->showEmailForm = false;
        $this->emailRecipients = '';
        $this->emailMessage = '';

        $this->rfi->load([
            'project:id,name,project_number',
            'requestedBy',
            'answeredBy',
            'documents',
            'emailDeliveries.sentBy:id,first_name,last_name',
        ]);

        $this->dispatch('notify', message: 'Formal RFI email sent and tracked.');
    }

    /**
     * @return array<int, string>
     */
    private function parseRecipients(): array
    {
        $recipients = collect(preg_split('/[;,\n\r]+/', $this->emailRecipients) ?: [])
            ->map(static fn (string $email): string => trim($email))
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'emailRecipients' => 'Please provide at least one recipient email address.',
            ]);
        }

        $invalidEmail = $recipients->first(
            static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) === false
        );

        if (is_string($invalidEmail)) {
            throw ValidationException::withMessages([
                'emailRecipients' => 'Invalid email address: '.$invalidEmail,
            ]);
        }

        return $recipients->all();
    }

    public function render()
    {
        return view('rfis::livewire.admin.rfis.show');
    }
}
