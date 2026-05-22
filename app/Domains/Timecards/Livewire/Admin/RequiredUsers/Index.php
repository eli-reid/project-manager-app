<?php

namespace App\Domains\Timecards\Livewire\Admin\RequiredUsers;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\TimecardRequiredUser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('payroll::layouts.payroll-admin')]
#[Title('Timecard Required Users')]
class Index extends Component
{
    public string $searchTerm = '';

    public function updatingSearchTerm(): void
    {
        $this->resetPage();
    }

    public function toggleRequired(User $user): void
    {
        $entry = TimecardRequiredUser::where('user_id', $user->id)->first();

        if ($entry) {
            $entry->delete();
            session()->flash('success', 'Removed required timecard status.');
        } else {
            TimecardRequiredUser::create([
                'user_id' => $user->id,
                'reminders_enabled' => true,
            ]);
            session()->flash('success', 'Marked employee as required for timecards.');
        }
    }

    public function updateRemindersEnabled(User $user, bool $enabled): void
    {
        TimecardRequiredUser::where('user_id', $user->id)->update([
            'reminders_enabled' => $enabled,
        ]);

        session()->flash('success', $enabled ? 'Enabled reminders for this employee.' : 'Disabled reminders for this employee.');
    }

    public function setEffectiveDates(User $user, ?string $startDate, ?string $endDate): void
    {
        if (blank($startDate) && blank($endDate)) {
            TimecardRequiredUser::where('user_id', $user->id)->update([
                'effective_start_date' => null,
                'effective_end_date' => null,
            ]);

            session()->flash('success', 'Cleared effective dates.');

            return;
        }

        $parsedStart = filled($startDate) ? Carbon::parse($startDate)->startOfDay() : null;
        $parsedEnd = filled($endDate) ? Carbon::parse($endDate)->endOfDay() : null;

        if ($parsedStart !== null && $parsedEnd !== null && $parsedEnd->lt($parsedStart)) {
            $this->addError('effectiveDates', 'End date must be on or after start date.');

            return;
        }

        TimecardRequiredUser::where('user_id', $user->id)->update([
            'effective_start_date' => $parsedStart,
            'effective_end_date' => $parsedEnd,
        ]);

        $this->resetErrorBag('effectiveDates');
        session()->flash('success', 'Updated effective dates.');
    }

    /**
     * @return Collection<int, array{user: User, entry: TimecardRequiredUser|null, is_required: bool}>
     */
    private function getUsers(): Collection
    {
        $query = User::query()
            ->where('is_active', true)
            ->where('is_built_in', false);

        if ($this->searchTerm) {
            $query->where(function ($q): void {
                $q->where('first_name', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('last_name', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$this->searchTerm.'%');
            });
        }

        $users = $query->get();
        $entriesByUserId = TimecardRequiredUser::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return $users->map(function (User $user) use ($entriesByUserId): array {
            $entry = $entriesByUserId->get($user->id);

            return [
                'user' => $user,
                'entry' => $entry,
                'is_required' => $entry !== null,
            ];
        });
    }

    public function render()
    {
        $users = $this->getUsers();

        return view('timecards::livewire.admin.required-users.index', [
            'users' => $users,
        ]);
    }
}
