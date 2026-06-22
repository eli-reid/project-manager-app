<section class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="space-y-2">
        <flux:heading size="xl">{{ __('My Pay Stubs') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Review payroll statements, withholding details, and downloadable pay stubs.') }}
        </flux:text>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pay Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pay Period</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Gross</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Taxes</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Net</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Stub</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($stubs as $stub)
                        @php
                            $taxTotal = (float) $stub->federal_tax + (float) $stub->state_tax + (float) $stub->local_tax + (float) $stub->social_security + (float) $stub->medicare;
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ optional($stub->payRun?->pay_date)->format('M j, Y') ?? 'N/A' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ optional($stub->payRun?->pay_period_start)->format('M j') ?? 'N/A' }} - {{ optional($stub->payRun?->pay_period_end)->format('M j, Y') ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->gross_pay, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">${{ number_format($taxTotal, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">${{ number_format((float) $stub->net_pay, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <a
                                    href="{{ route('payroll.history.show', ['payrollStatement' => $stub]) }}"
                                    wire:navigate
                                    class="inline-flex items-center rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No pay stubs are available yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $stubs->links() }}
    </div>
</section>
