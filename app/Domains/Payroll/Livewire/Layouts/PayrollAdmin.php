<?php

namespace App\Domains\Payroll\Livewire\Layouts;

use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Timecards\Models\Timecard;
use App\Support\Contracts\ProvidesDomainNavbar;
use Illuminate\View\View;
use Livewire\Component;

class PayrollAdmin extends Component implements ProvidesDomainNavbar
{
    /**
     * @return array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public static function navbarItems(): array
    {
        return array_values(array_filter([
            auth()->user()?->can('payroll-timecards.view')
                ? [
                    'label' => (string) __('Timecards'),
                    'href' => route('admin.timecards.index'),
                    'current' => request()->routeIs('admin.timecards.*')
                        && ! request()->routeIs('admin.timecards.required-users*'),
                ]
                : null,
            auth()->user()?->can('viewAll', Timecard::class)
                ? [
                    'label' => (string) __('Required Users'),
                    'href' => route('admin.timecards.required-users'),
                    'current' => request()->routeIs('admin.timecards.required-users*'),
                ]
                : null,
            auth()->user()?->can('viewAll', DailyReport::class)
                ? [
                    'label' => (string) __('Dailies'),
                    'href' => route('admin.dailies.index'),
                    'current' => request()->routeIs('admin.dailies.*'),
                ]
                : null,
            auth()->user()?->can('payroll-timecards.view')
                ? [
                    'label' => (string) __('Timecard Review'),
                    'href' => route('admin.payroll.timecards.review'),
                    'current' => request()->routeIs('admin.payroll.timecards.*'),
                ]
                : null,
            auth()->user()?->can('payroll-runs.preview')
                ? [
                    'label' => (string) __('Weekly Employee Hours'),
                    'href' => route('admin.payroll.reports.weekly-employee-hours'),
                    'current' => request()->routeIs('admin.payroll.reports.weekly-employee-hours'),
                ]
                : null,
            auth()->user()?->can('payroll-runs.preview')
                ? [
                    'label' => (string) __('Weekly Hour Adjustments'),
                    'href' => route('admin.payroll.reports.weekly-hour-adjustments'),
                    'current' => request()->routeIs('admin.payroll.reports.weekly-hour-adjustments'),
                ]
                : null,
            auth()->user()?->can('payroll-rates.view')
                ? [
                    'label' => (string) __('Rates'),
                    'href' => route('admin.payroll.rates.index'),
                    'current' => request()->routeIs('admin.payroll.rates.*'),
                ]
                : null,
            auth()->user()?->can('payroll-rates.view')
                ? [
                    'label' => (string) __('Rate Types'),
                    'href' => route('admin.payroll.rate-types.index'),
                    'current' => request()->routeIs('admin.payroll.rate-types.*'),
                ]
                : null,
            auth()->user()?->can('payroll-runs.preview')
                ? [
                    'label' => (string) __('Runs'),
                    'href' => route('admin.payroll.runs.index'),
                    'current' => request()->routeIs('admin.payroll.runs.*'),
                ]
                : null,
        ]));
    }

    public function render(): View
    {
        return view('payroll::livewire.layouts.payroll-admin');
    }
}
