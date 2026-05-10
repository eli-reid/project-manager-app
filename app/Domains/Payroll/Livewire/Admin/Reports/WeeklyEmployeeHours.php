<?php

namespace App\Domains\Payroll\Livewire\Admin\Reports;

use App\Core\Audit\Contracts\AuditLoggerContract;
use App\Core\Identity\Models\User;
use App\Core\Settings\Services\WeekSettingsService;
use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use App\Domains\Payroll\Models\WeeklyEmployeeHoursAdjustment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Weekly Employee Hours')]
class WeeklyEmployeeHours extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'week_start')]
    public ?string $weekStart = null;

    /**
     * @var array<string, string>
     */
    public array $editHours = [];

    /**
     * @var array<string, string>
     */
    public array $editReasons = [];

    public ?string $editingUserId = null;

    public function mount(): void
    {
        $this->authorize('payroll-runs.preview');

        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        if (! $this->weekStart) {
            $this->weekStart = today()->startOfWeek($weekStartsAt)->toDateString();
        }

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->startOfWeek($weekStartsAt)
            ->toDateString();
    }

    public function getWeekEndProperty(): CarbonImmutable
    {
        return app(WeekSettingsService::class)->weekEndFromStart($this->weekStart);
    }

    public function getEmployeeHoursProperty(): Collection
    {
        $weekStart = Carbon::parse($this->weekStart)->startOfDay();

        $baseHours = app(PayrollTimecardReadGateway::class)
            ->weeklyEmployeeHoursForWeek($weekStart);

        $adjustments = WeeklyEmployeeHoursAdjustment::query()
            ->whereDate('week_start', $weekStart->toDateString())
            ->with(['editor:id,first_name,last_name'])
            ->get()
            ->keyBy('user_id');

        $baseByUser = $baseHours->keyBy('user_id');

        $allUserIds = $baseByUser->keys()
            ->merge($adjustments->keys())
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn('id', $allUserIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        return $allUserIds
            ->map(function (string $userId) use ($baseByUser, $adjustments, $users): array {
                $base = $baseByUser->get($userId);
                $adjustment = $adjustments->get($userId);
                $employee = $users->get($userId);

                $sourceHours = (float) ($base['hours'] ?? 0.0);
                $effectiveHours = $adjustment
                    ? (float) $adjustment->adjusted_hours
                    : $sourceHours;

                return [
                    'user_id' => $userId,
                    'first_name' => (string) ($base['first_name'] ?? $employee?->first_name ?? ''),
                    'last_name' => (string) ($base['last_name'] ?? $employee?->last_name ?? ''),
                    'source_hours' => round($sourceHours, 2),
                    'hours' => round($effectiveHours, 2),
                    'is_adjusted' => $adjustment !== null,
                    'adjustment_reason' => $adjustment?->reason,
                    'adjusted_at' => $adjustment?->edited_at,
                    'adjusted_by' => $adjustment?->editor?->name,
                ];
            })
            ->sortBy(fn (array $item): string => strtolower(trim($item['first_name'].' '.$item['last_name'])))
            ->values();
    }

    public function getTotalHoursProperty(): float
    {
        return round($this->employeeHours->sum('hours'), 2);
    }

    public function previousWeek(): void
    {
        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->subWeek()
            ->startOfWeek($weekStartsAt)
            ->toDateString();

        $this->editingUserId = null;
        $this->editHours = [];
        $this->editReasons = [];
    }

    public function nextWeek(): void
    {
        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->addWeek()
            ->startOfWeek($weekStartsAt)
            ->toDateString();

        $this->editingUserId = null;
        $this->editHours = [];
        $this->editReasons = [];
    }

    public function updatedWeekStart(): void
    {
        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->startOfWeek($weekStartsAt)
            ->toDateString();

        $this->editingUserId = null;
        $this->editHours = [];
        $this->editReasons = [];
    }

    public function getCanAdjustHoursProperty(): bool
    {
        return Gate::allows('payroll-runs.adjust-hours');
    }

    public function startEditing(string $userId): void
    {
        if (! $this->canAdjustHours) {
            abort(403);
        }

        $row = $this->employeeHours->firstWhere('user_id', $userId);

        if ($row === null) {
            return;
        }

        $this->editingUserId = $userId;
        $this->editHours[$userId] = number_format((float) $row['hours'], 2, '.', '');
        $this->editReasons[$userId] = (string) ($row['adjustment_reason'] ?? '');
    }

    public function cancelEditing(): void
    {
        $this->editingUserId = null;
    }

    public function saveAdjustment(string $userId): void
    {
        if (! $this->canAdjustHours) {
            abort(403);
        }

        $validated = $this->validate([
            "editHours.{$userId}" => ['required', 'numeric', 'min:0', 'max:168'],
            "editReasons.{$userId}" => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $row = $this->employeeHours->firstWhere('user_id', $userId);

        if ($row === null) {
            throw ValidationException::withMessages([
                "editHours.{$userId}" => 'Unable to find the selected employee row for this week.',
            ]);
        }

        $sourceHours = round((float) $row['source_hours'], 2);
        $adjustedHours = round((float) $validated['editHours'][$userId], 2);
        $reason = trim((string) $validated['editReasons'][$userId]);

        $editor = Auth::user();
        abort_unless($editor instanceof User, 401);

        $existing = WeeklyEmployeeHoursAdjustment::query()
            ->whereDate('week_start', $this->weekStart)
            ->where('user_id', $userId)
            ->first();

        if (abs($adjustedHours - $sourceHours) < 0.01) {
            if ($existing !== null) {
                app(AuditLoggerContract::class)->record('payroll.weekly-hours.adjustment.cleared', $existing, [
                    'before' => [
                        'week_start' => (string) $existing->week_start?->toDateString(),
                        'source_hours' => (float) $existing->source_hours,
                        'adjusted_hours' => (float) $existing->adjusted_hours,
                        'reason' => (string) $existing->reason,
                    ],
                    'after' => [
                        'week_start' => $this->weekStart,
                        'source_hours' => $sourceHours,
                        'adjusted_hours' => $sourceHours,
                        'reason' => 'Override cleared because adjusted hours match source hours.',
                    ],
                    'user_id' => $userId,
                ]);

                $existing->delete();
            }

            $this->editingUserId = null;

            return;
        }

        $before = $existing === null
            ? null
            : [
                'week_start' => (string) $existing->week_start?->toDateString(),
                'source_hours' => (float) $existing->source_hours,
                'adjusted_hours' => (float) $existing->adjusted_hours,
                'reason' => (string) $existing->reason,
            ];

        $adjustment = WeeklyEmployeeHoursAdjustment::query()->updateOrCreate(
            [
                'week_start' => $this->weekStart,
                'user_id' => $userId,
            ],
            [
                'source_hours' => $sourceHours,
                'adjusted_hours' => $adjustedHours,
                'reason' => $reason,
                'edited_by_id' => (string) $editor->id,
                'edited_at' => now(),
                'metadata' => [
                    'source' => 'weekly-employee-hours-report',
                ],
            ],
        );

        app(AuditLoggerContract::class)->record('payroll.weekly-hours.adjusted', $adjustment, [
            'before' => $before,
            'after' => [
                'week_start' => $this->weekStart,
                'source_hours' => $sourceHours,
                'adjusted_hours' => $adjustedHours,
                'reason' => $reason,
            ],
            'user_id' => $userId,
        ]);

        $this->editingUserId = null;
    }

    public function render()
    {
        return view('payroll::livewire.admin.reports.weekly-employee-hours.index');
    }
}
