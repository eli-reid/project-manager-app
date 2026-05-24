<?php

namespace App\Domains\Dailies\Livewire\Admin\Dailies;

use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Dailies\Services\DailyReportLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('timecards::livewire.layouts.time-management-admin')]
#[Title('Daily Report Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public DailyReport $dailyReport;

    public string $rejectionReason = '';

    public function mount(DailyReport $dailyReport): void
    {
        $this->authorize('view', $dailyReport);

        $this->dailyReport = $dailyReport->load(['project', 'user', 'submittedBy']);
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->dailyReport);

        $approver = Auth::user();
        abort_unless($approver !== null, 401);

        try {
            $this->dailyReport = app(DailyReportLifecycleService::class)
                ->approve($this->dailyReport, $approver);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                $this->addError('dailyReport', $messages[0]);
            }

            return;
        }

        session()->flash('success', 'Daily report approved.');
    }

    public function reject(): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $this->authorize('reject', $this->dailyReport);

        try {
            $this->dailyReport = app(DailyReportLifecycleService::class)
                ->reject($this->dailyReport, $this->rejectionReason);
            $this->rejectionReason = '';
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                $this->addError('dailyReport', $messages[0]);
            }

            return;
        }

        session()->flash('success', 'Daily report rejected.');
    }

    public function render()
    {
        return view('dailies::livewire.admin.dailies.show', [
            'dailyReport' => $this->dailyReport,
        ]);
    }
}
