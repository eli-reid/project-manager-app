<?php

namespace App\Core\Scheduler\Livewire\Admin\Settings;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Settings\Facades\Settings;
use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\SettingsSqliteService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Scheduler System Timing')]
class SystemTiming extends Component
{
    use AuthorizesRequests;

    public int $claimWindowSeconds = 300;

    public ?string $successMessage = null;

    public bool $canEdit = false;

    public function mount(): void
    {
        $this->authorize('viewAny', ScheduledTask::class);

        $this->canEdit = auth()->user()?->can('update', SettingsSqlite::class) ?? false;
        $this->claimWindowSeconds = $this->resolveClaimWindowSeconds();
    }

    public function save(): void
    {
        $this->authorize('update', SettingsSqlite::class);

        $validated = $this->validate([
            'claimWindowSeconds' => ['required', 'integer', 'min:1', 'max:86400'],
        ], [], [
            'claimWindowSeconds' => 'scheduler claim window',
        ]);

        app(SettingsSqliteService::class)->set(
            'scheduler.claim_window_seconds',
            (string) $validated['claimWindowSeconds']
        );

        $this->claimWindowSeconds = (int) $validated['claimWindowSeconds'];
        $this->successMessage = 'Scheduler timing settings updated.';
    }

    /**
     * @return array<string, string>
     */
    public function timingSnapshot(): array
    {
        $appTimezone = $this->appTimezone();
        $nowUtc = CarbonImmutable::now('UTC');
        $nowApp = $nowUtc->setTimezone($appTimezone);

        return [
            'app_timezone' => $appTimezone,
            'app_now' => $nowApp->format('M j, Y g:i:s A T'),
            'utc_now' => $nowUtc->format('M j, Y g:i:s A T'),
            'storage_timezone' => 'UTC',
        ];
    }

    public function render()
    {
        return view('scheduler::livewire.admin.settings.system-timing', [
            'timing' => $this->timingSnapshot(),
        ]);
    }

    private function resolveClaimWindowSeconds(): int
    {
        $rawValue = Settings::get('scheduler.claim_window_seconds', env('SCHEDULER_CLAIM_WINDOW_SECONDS', 300))->raw();

        return max(1, (int) $rawValue);
    }

    private function appTimezone(): string
    {
        $rawValue = Settings::get('app.timezone', config('app.timezone', 'UTC'))->raw();

        return is_string($rawValue) && $rawValue !== ''
            ? $rawValue
            : 'UTC';
    }
}
