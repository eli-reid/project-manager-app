<?php

namespace App\Domains\Reports\Livewire\User\LeaveBalanceSummary;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Services\LeaveBalanceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Leave Balance Summary')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('reports.operational.view');
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.operational.view');

        $rows = $this->buildRows();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Leave Balance Summary']);
            fputcsv($handle, ['Generated', now()->toDateString()]);
            fputcsv($handle, []);
            fputcsv($handle, [
                'Employee',
                'Sick Allotted (hrs)',
                'Sick Used (hrs)',
                'Sick Remaining (hrs)',
                'Vacation Allotted (hrs)',
                'Vacation Used (hrs)',
                'Vacation Remaining (hrs)',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    number_format($row['sick_allowed'], 2, '.', ''),
                    number_format($row['sick_used'], 2, '.', ''),
                    number_format($row['sick_remaining'], 2, '.', ''),
                    number_format($row['vacation_allowed'], 2, '.', ''),
                    number_format($row['vacation_used'], 2, '.', ''),
                    number_format($row['vacation_remaining'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'leave-balance-summary-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('reports::livewire.user.leave-balance-summary.index', [
            'rows' => $this->buildRows(),
        ]);
    }

    /**
     * @return array<int, array{name:string,sick_allowed:float,sick_used:float,sick_remaining:float,vacation_allowed:float,vacation_used:float,vacation_remaining:float}>
     */
    private function buildRows(): array
    {
        $service = app(LeaveBalanceService::class);

        return User::query()
            ->where('is_active', true)
            ->where('is_built_in', false)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (User $user) use ($service): array {
                $balances = $service->forUser($user);

                return [
                    'name' => $user->name,
                    'sick_allowed' => $balances['sick']['allowed'],
                    'sick_used' => $balances['sick']['used'],
                    'sick_remaining' => $balances['sick']['remaining'],
                    'vacation_allowed' => $balances['vacation']['allowed'],
                    'vacation_used' => $balances['vacation']['used'],
                    'vacation_remaining' => $balances['vacation']['remaining'],
                ];
            })
            ->all();
    }
}
