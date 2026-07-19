<?php

namespace App\Livewire\Nav;

use App\Core\Announcement\Models\Announcement;
use App\Core\Auth\Role\Models\Role;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Domains\Accounting\Models\AccountingCode;
use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Models\Client;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class SidebarAdminNav extends Component
{
    public function render(): View
    {
        return view('livewire.nav.admin.sidebar', $this->getNavPermissions());
    }

    /**
     * @return array<string, mixed>
     */
    private function getNavPermissions(): array
    {
        $user = Auth::user();

        $canManageAnnouncements = $user?->can('viewAny', Announcement::class) ?? false;
        $canManageUsers = $user?->can('admin') ?? false;
        $canViewRoles = $user?->can('viewAny', Role::class) ?? false;
        $canManageEmailAccounts = $user?->can('manage-email-accounts') ?? false;
        $showAccessManagement = $canManageUsers || $canViewRoles || $canManageEmailAccounts;
        $accessManagementHref = match (true) {
            $canManageUsers => route('dashboard', ['panel' => 'access-users']),
            $canViewRoles => route('dashboard', ['panel' => 'access-roles']),
            default => route('dashboard', ['panel' => 'access-email-management']),
        };
        $canViewAdminClients = $user?->can('viewAny', Client::class) ?? false;
        $canViewAdminAddresses = $user?->can('viewAny', Address::class) ?? false;
        $showClientManagement = $canViewAdminClients || $canViewAdminAddresses;
        $canViewAdminProjects = $user?->can('viewAny', Project::class) ?? false;
        $canViewAdminAccountingCodes = $user?->can('viewAny', AccountingCode::class) ?? false;
        $canViewAdminStockOrders = $user?->can('viewAny', StockOrder::class) ?? false;
        $canViewAdminStockTemplates = $user?->can('viewAny', StockOrderTemplate::class) ?? false;
        $canViewAdminInvoices = $user?->can('viewAny', Invoice::class) ?? false;
        $showStockAndInvoices = $canViewAdminStockOrders || $canViewAdminStockTemplates || $canViewAdminInvoices;
        $canViewAdminDailies = $user?->can('viewAll', DailyReport::class) ?? false;
        $canViewAdminTimecards = $user?->can('viewAll', Timecard::class) ?? false;
        $showTimeManagement = $canViewAdminDailies || $canViewAdminTimecards;
        $canViewAdminDocuments = $user?->can('deleteAny', Document::class) ?? false;
        $canPayrollTimecards = $user?->can('payroll-timecards.view') ?? false;
        $canPayrollRates = $user?->can('payroll-rates.view') ?? false;
        $canManagePayroll = $canPayrollTimecards
            || $canPayrollRates
            || ($user?->can('payroll-runs.preview') ?? false);
        $payrollHref = match (true) {
            $canPayrollTimecards => route('admin.payroll.timecards.review'),
            $canPayrollRates => route('admin.payroll.rates.index'),
            default => route('admin.payroll.runs.index'),
        };
        $timeManagementHref = $canViewAdminTimecards
            ? route('admin.timecards.index')
            : route('admin.dailies.index');
        $canViewReports = ($user?->can('reports.financial.view') ?? false)
            || ($user?->can('reports.operational.view') ?? false);
        $canManageSettings = $user?->can('admin') ?? false;
        $canViewScheduler = $user?->can('viewAny', ScheduledTask::class) ?? false;
        $canViewQueue = $user?->can('queue.viewAny') ?? false;
        $canViewAdminNav = $user?->hasPermission('navigation.view-admin') ?? false;

        return [
            'canManageAnnouncements' => $canManageAnnouncements,
            'canManageUsers' => $canManageUsers,
            'showAccessManagement' => $showAccessManagement,
            'accessManagementHref' => $accessManagementHref,
            'canViewAdminClients' => $canViewAdminClients,
            'canViewAdminAddresses' => $canViewAdminAddresses,
            'showClientManagement' => $showClientManagement,
            'canViewAdminProjects' => $canViewAdminProjects,
            'canViewAdminAccountingCodes' => $canViewAdminAccountingCodes,
            'canViewAdminStockOrders' => $canViewAdminStockOrders,
            'canViewAdminStockTemplates' => $canViewAdminStockTemplates,
            'canViewAdminInvoices' => $canViewAdminInvoices,
            'showStockAndInvoices' => $showStockAndInvoices,
            'canViewAdminDailies' => $canViewAdminDailies,
            'canViewAdminTimecards' => $canViewAdminTimecards,
            'showTimeManagement' => $showTimeManagement,
            'timeManagementHref' => $timeManagementHref,
            'canViewAdminDocuments' => $canViewAdminDocuments,
            'canManagePayroll' => $canManagePayroll,
            'payrollHref' => $payrollHref,
            'canViewReports' => $canViewReports,
            'canManageSettings' => $canManageSettings,
            'canViewScheduler' => $canViewScheduler,
            'canViewQueue' => $canViewQueue,
            'canViewAdminNav' => $canViewAdminNav,
        ];
    }
}
