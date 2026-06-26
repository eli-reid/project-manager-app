<?php

namespace App\Domains\Payroll\Livewire\User\Reports\Audit;

use App\Core\Audit\Models\AuditLog;
use App\Domains\Payroll\Models\PayrollAuditDigest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Payroll Audit Trail')]
class Index extends Component
{
    use AuthorizesRequests;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $actionContains = '';

    public bool $invalidDigestsOnly = false;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
        $this->fromDate = now()->subDays(30)->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.payroll.export');

        $rows = $this->rows();
        $from = $this->fromDate ?? now()->subDays(30)->toDateString();
        $to = $this->toDate ?? now()->toDateString();

        $fileName = 'payroll-audit-trail-'.Str::slug($from.'-to-'.$to).'.csv';

        return response()->streamDownload(function () use ($rows, $from, $to): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Payroll Audit Trail']);
            fputcsv($handle, ['From', $from]);
            fputcsv($handle, ['To', $to]);
            fputcsv($handle, []);
            fputcsv($handle, ['When', 'Action', 'Actor', 'Target', 'Digest Valid']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['created_at'],
                    $row['action'],
                    $row['actor'],
                    $row['target'],
                    $row['digest_valid'] ? 'yes' : 'no',
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        $rows = $this->rows();

        return view('payroll::livewire.user.reports.audit.index', [
            'rows' => $rows,
            'summary' => [
                'total' => $rows->count(),
                'invalid' => $rows->where('digest_valid', false)->count(),
            ],
        ]);
    }

    /**
     * @return Collection<int, array{created_at:string,action:string,actor:string,target:string,digest_valid:bool,metadata:array<mixed>}>
     */
    private function rows(): Collection
    {
        $query = AuditLog::query()
            ->where('action', 'like', 'payroll.%')
            ->whereDate('created_at', '>=', (string) $this->fromDate)
            ->whereDate('created_at', '<=', (string) $this->toDate)
            ->when($this->actionContains !== '', function ($builder): void {
                $builder->where('action', 'like', '%'.$this->actionContains.'%');
            })
            ->latest('created_at')
            ->limit(200);

        $logs = $query->get();

        $digests = PayrollAuditDigest::query()
            ->whereIn('audit_log_id', $logs->pluck('id')->values())
            ->get()
            ->keyBy('audit_log_id');

        $rows = $logs->map(function (AuditLog $log) use ($digests): array {
            $digest = $digests->get($log->id);
            $actor = $log->actor_id !== null ? class_basename((string) $log->actor_type).'#'.$log->actor_id : 'System';
            $target = $log->target_id !== null ? class_basename((string) $log->target_type).'#'.$log->target_id : 'N/A';

            return [
                'created_at' => $log->created_at?->toDateTimeString() ?? '',
                'action' => $log->action,
                'actor' => $actor,
                'target' => $target,
                'digest_valid' => (bool) ($digest?->is_valid ?? false),
                'metadata' => is_array($log->metadata) ? $log->metadata : [],
            ];
        });

        if ($this->invalidDigestsOnly) {
            return $rows->where('digest_valid', false)->values();
        }

        return $rows;
    }
}
