<?php

namespace App\Core\User\Livewire\Settings\TwoFactor;

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class RecoveryCodes extends Component
{
    #[Reactive]
    public bool $requiresConfirmation = true;

    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    #[Computed]
    public function canShowRecoveryCodes(): bool
    {
        if (! auth()->user()->hasEnabledTwoFactorAuthentication()) {
            return false;
        }

        if (! $this->requiresConfirmation) {
            return true;
        }

        return filled(auth()->user()->two_factor_confirmed_at);
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        if (! auth()->user()->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        if ($this->requiresConfirmation && ! filled(auth()->user()->two_factor_confirmed_at)) {
            return;
        }

        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    private function loadRecoveryCodes(): void
    {
        if (! auth()->user()->hasEnabledTwoFactorAuthentication()) {
            $this->recoveryCodes = [];

            return;
        }

        if ($this->requiresConfirmation && ! filled(auth()->user()->two_factor_confirmed_at)) {
            $this->recoveryCodes = [];

            return;
        }

        try {
            $codes = auth()->user()->recoveryCodes();
            $this->recoveryCodes = is_array($codes) ? $codes : [];
        } catch (\Throwable) {
            $this->addError('recoveryCodes', 'Unable to load recovery codes.');
            $this->recoveryCodes = [];
        }
    }

    public function render()
    {
        return view('core-user::livewire.settings.two-factor.recovery-codes');
    }
}
