<?php

namespace App\Domains\Database\Seeders;

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Addresses\Database\Factories\AddressFactory;
use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Database\Factories\ClientFactory;
use App\Domains\Clients\Models\Client;
use App\Domains\Dailies\Database\Factories\DailyReportFactory;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Database\Factories\DocumentFactory;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Database\Factories\InvoiceFactory;
use App\Domains\Invoices\Database\Factories\InvoiceLineItemFactory;
use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Models\InvoiceLineItem;
use App\Domains\Payroll\Database\Factories\DeductionFactory;
use App\Domains\Payroll\Database\Factories\PayrollEmployeeProfileFactory;
use App\Domains\Payroll\Database\Factories\PayrollStatementFactory;
use App\Domains\Payroll\Enums\PayRunStatus;
use App\Domains\Payroll\Models\Deduction;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Projects\Database\Factories\CostCodeFactory;
use App\Domains\Projects\Database\Factories\ProjectFactory;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Database\Factories\StockOrderFactory;
use App\Domains\Stock\Database\Factories\StockOrderItemFactory;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderItem;
use App\Domains\Timecards\Database\Factories\TimecardEntryFactory;
use App\Domains\Timecards\Database\Factories\TimecardFactory;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DomainDemoSeeder extends Seeder
{
    private const PROJECT_COUNT = 10;

    private const DEMO_USER_COUNT = 15;

    private const TIMECARD_WEEKS = 4;

    public function run(): void
    {
        $users = $this->seedUsers();
        $clientsWithAddresses = $this->seedClientsWithAddresses();
        $projects = $this->seedProjects($users, $clientsWithAddresses);

        $this->seedProjectData($projects, $users);
        $this->seedDailies($users, $projects);
        $this->seedTimecards($users, $projects);
        $this->seedPayroll($users, $projects);
    }

    /**
     * @return Collection<int, User>
     */
    private function seedUsers(): Collection
    {
        $demoUsers = collect(range(1, self::DEMO_USER_COUNT))
            ->map(function (int $index): User {
                return User::query()->updateOrCreate(
                    ['email' => "demo.user{$index}@example.com"],
                    [
                        'first_name' => fake()->firstName(),
                        'last_name' => fake()->lastName(),
                        'username' => "demouser{$index}",
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'is_admin' => false,
                        'is_built_in' => false,
                        'is_active' => true,
                        'password_change_required' => false,
                    ]
                );
            });

        $userRoleId = Role::query()->where('name', Role::BUILT_IN_USER)->value('id');
        if ($userRoleId !== null) {
            $demoUsers->each(fn (User $user): User => tap($user, fn (User $target): mixed => $target->roles()->syncWithoutDetaching([$userRoleId])));
        }

        return User::query()
            ->where('is_active', true)
            ->get();
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Project>  $projects
     */
    private function seedDailies(Collection $users, Collection $projects): void
    {
        $workers = $users->where('is_admin', false)->values();

        $workers->each(function (User $worker) use ($projects, $users): void {
            foreach (range(0, self::TIMECARD_WEEKS - 1) as $dayOffset) {
                $reportDate = now()->copy()->subDays($dayOffset)->toDateString();
                $project = $projects->random();
                $regularHours = fake()->randomFloat(2, 6, 9);
                $overtimeHours = fake()->randomFloat(2, 0, 2);

                DailyReport::query()->updateOrCreate(
                    [
                        'user_id' => $worker->id,
                        'project_id' => $project->id,
                        'report_date' => $reportDate,
                    ],
                    DailyReportFactory::new()->for($worker)->for($project)->make([
                        'report_date' => $reportDate,
                        'submitted_by_id' => $worker->id,
                        'status' => collect([
                            DailyReport::STATUS_DRAFT,
                            DailyReport::STATUS_SUBMITTED,
                            DailyReport::STATUS_APPROVED,
                        ])->random(),
                        'work_performed' => [
                            ['task' => fake()->sentence(4), 'hours' => $regularHours],
                        ],
                        'materials_used' => [
                            ['item' => fake()->words(2, true), 'quantity' => rand(1, 12), 'unit' => 'ea'],
                        ],
                        'equipment_used' => [
                            ['name' => fake()->randomElement(['Lift', 'Skid Steer', 'Generator']), 'hours' => fake()->randomFloat(2, 0.5, 6)],
                        ],
                        'onsite_employees' => [
                            ['name' => $worker->first_name.' '.$worker->last_name, 'role' => 'Crew', 'hours' => round($regularHours + $overtimeHours, 2)],
                            ['name' => $users->random()->first_name.' '.$users->random()->last_name, 'role' => 'Foreman', 'hours' => fake()->randomFloat(2, 4, 10)],
                        ],
                        'total_regular_hours' => $regularHours,
                        'total_overtime_hours' => $overtimeHours,
                        'total_hours' => round($regularHours + $overtimeHours, 2),
                    ])->toArray()
                );
            }
        });
    }

    /**
     * @return Collection<int, array{client: Client, address: Address}>
     */
    private function seedClientsWithAddresses(): Collection
    {
        return collect(range(1, self::PROJECT_COUNT))
            ->map(function (int $index): array {
                $client = ClientFactory::new()->create([
                    'company_name' => "Demo Client {$index}",
                ]);

                $address = AddressFactory::new()->for($client)->create();

                return [
                    'client' => $client,
                    'address' => $address,
                ];
            });
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, array{client: Client, address: Address}>  $clientsWithAddresses
     * @return Collection<int, Project>
     */
    private function seedProjects(Collection $users, Collection $clientsWithAddresses): Collection
    {
        $projectManagers = $users->shuffle()->take(self::PROJECT_COUNT)->values();
        $statusValues = array_keys(ProjectStatusEnum::toArray());

        return collect(range(1, self::PROJECT_COUNT))
            ->map(function (int $index) use ($clientsWithAddresses, $projectManagers, $statusValues): Project {
                $clientData = $clientsWithAddresses->get($index - 1);
                $projectManager = $projectManagers->get($index - 1);

                $projectAttributes = ProjectFactory::new()->make([
                    'project_number' => sprintf('DEMO-%04d', $index),
                    'name' => "Demo Project {$index}",
                    'status' => $statusValues[array_rand($statusValues)],
                    'client_id' => $clientData['client']->id,
                    'address_id' => $clientData['address']->id,
                    'project_manager_id' => $projectManager?->id,
                    'budget' => fake()->randomFloat(2, 50000, 1500000),
                    'is_prevailing_wage' => $index % 3 === 0,
                ])->toArray();

                unset($projectAttributes['leave_category']);

                return Project::query()->updateOrCreate(
                    ['project_number' => sprintf('DEMO-%04d', $index)],
                    $projectAttributes
                );
            });
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, User>  $users
     */
    private function seedProjectData(Collection $projects, Collection $users): void
    {
        $projects->each(function (Project $project, int $index) use ($users): void {
            $costCodes = collect(range(1, 4))
                ->map(fn (int $costCodeIndex): CostCode => CostCode::query()->updateOrCreate(
                    ['project_id' => $project->id, 'code' => sprintf('%03d-%02d', $index + 1, $costCodeIndex)],
                    CostCodeFactory::new()->for($project)->make([
                        'code' => sprintf('%03d-%02d', $index + 1, $costCodeIndex),
                    ])->toArray()
                ));

            collect(range(1, 3))->each(function (int $invoiceIndex) use ($project, $users): void {
                $creator = $users->random();

                $invoice = Invoice::query()->updateOrCreate(
                    ['invoice_number' => sprintf('DEMO-INV-%s-%02d', $project->project_number, $invoiceIndex)],
                    InvoiceFactory::new()->for($project)->make([
                        'invoice_number' => sprintf('DEMO-INV-%s-%02d', $project->project_number, $invoiceIndex),
                        'project_id' => $project->id,
                        'created_by' => $creator->id,
                        'status' => collect(InvoiceStatusEnum::cases())->random()->value,
                    ])->toArray()
                );

                InvoiceLineItem::query()->where('invoice_id', $invoice->id)->delete();

                $lineItems = collect(range(1, rand(2, 5)))
                    ->map(fn (int $lineItemIndex): InvoiceLineItem => InvoiceLineItemFactory::new()->for($invoice)->create([
                        'sort_order' => $lineItemIndex,
                    ]));

                $subtotal = round($lineItems->sum('total'), 2);
                $taxAmount = round($subtotal * 0.0825, 2);

                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => round($subtotal + $taxAmount, 2),
                ]);
            });

            collect(range(1, 2))->each(function (int $orderIndex) use ($project, $users): void {
                $order = StockOrder::query()->updateOrCreate(
                    ['po_number' => sprintf('DEMO-PO-%s-%02d', $project->project_number, $orderIndex)],
                    StockOrderFactory::new()->forProject($project)->make([
                        'po_number' => sprintf('DEMO-PO-%s-%02d', $project->project_number, $orderIndex),
                        'project_id' => $project->id,
                        'user_id' => $users->random()->id,
                    ])->toArray()
                );

                StockOrderItem::query()->where('stock_order_id', $order->id)->delete();

                collect(range(1, rand(2, 5)))
                    ->each(fn (): StockOrderItem => StockOrderItemFactory::new()->for($order)->create());
            });

            $projectDocuments = collect(range(1, 2))
                ->map(function (int $docIndex) use ($project, $users): Document {
                    $document = Document::query()->updateOrCreate(
                        ['stored_name' => sprintf('demo-project-%s-%02d.pdf', strtolower($project->project_number), $docIndex)],
                        DocumentFactory::new()->projectOwned()->make([
                            'stored_name' => sprintf('demo-project-%s-%02d.pdf', strtolower($project->project_number), $docIndex),
                            'uploaded_by_id' => $users->random()->id,
                            'storage_path' => sprintf('documents/projects/%s/demo-%02d.pdf', strtolower($project->project_number), $docIndex),
                        ])->toArray()
                    );

                    $document->update([
                        'owner_scope' => Document::OWNER_SCOPE_PROJECT,
                        'owner_id' => $project->id,
                    ]);

                    return $document;
                });

            $userOwner = $users->random();

            $userDocument = Document::query()->updateOrCreate(
                ['stored_name' => sprintf('demo-user-guide-%s.pdf', strtolower($project->project_number))],
                DocumentFactory::new()->make([
                    'title' => $project->name.' User Guide',
                    'owner_scope' => Document::OWNER_SCOPE_USER,
                    'owner_id' => $userOwner->id,
                    'stored_name' => sprintf('demo-user-guide-%s.pdf', strtolower($project->project_number)),
                    'uploaded_by_id' => $users->random()->id,
                ])->toArray()
            );

            $userDocument->update([
                'owner_scope' => Document::OWNER_SCOPE_USER,
                'owner_id' => $userOwner->id,
            ]);

            if ($projectDocuments->isEmpty()) {
                return;
            }

            $project->costCodes()->whereNotIn('id', $costCodes->pluck('id'))->delete();
        });
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Project>  $projects
     */
    private function seedTimecards(Collection $users, Collection $projects): void
    {
        $workers = $users->where('is_admin', false)->values();

        $workers->each(function (User $worker) use ($projects, $users): void {
            foreach (range(0, self::TIMECARD_WEEKS - 1) as $weekOffset) {
                $weekStart = now()->copy()->subWeeks($weekOffset)->startOfWeek();
                $weekEnd = $weekStart->copy()->endOfWeek();
                $weekStartingValue = $weekStart->copy()->startOfDay()->format('Y-m-d H:i:s');
                $weekEndingValue = $weekEnd->copy()->startOfDay()->format('Y-m-d H:i:s');

                $timecard = Timecard::query()->updateOrCreate(
                    [
                        'user_id' => $worker->id,
                        'week_starting' => $weekStartingValue,
                    ],
                    array_merge(
                        TimecardFactory::new()->for($worker)->make()->toArray(),
                        [
                            'week_starting' => $weekStartingValue,
                            'week_ending' => $weekEndingValue,
                            'status' => collect([
                                Timecard::STATUS_DRAFT,
                                Timecard::STATUS_SUBMITTED,
                                Timecard::STATUS_APPROVED,
                            ])->random(),
                            'approved_by' => $users->random()->id,
                            'approved_at' => now()->subDays(rand(0, 20)),
                            'submitted_at' => now()->subDays(rand(0, 25)),
                        ]
                    )
                );

                TimecardEntry::query()->where('timecard_id', $timecard->id)->delete();

                $hours = 0.0;

                foreach (range(1, 5) as $day) {
                    $project = $projects->random();
                    $costCode = CostCode::query()->where('project_id', $project->id)->inRandomOrder()->first();

                    $entry = TimecardEntryFactory::new()->for($timecard)->for($worker)->for($project)->create([
                        'date' => $weekStart->copy()->addDays($day - 1)->toDateString(),
                        'cost_code_id' => $costCode?->id,
                        'hours' => fake()->randomFloat(2, 6, 10),
                    ]);

                    $hours += (float) $entry->hours;
                }

                $timecard->update(['total_hours' => round($hours, 2)]);
            }
        });
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Project>  $projects
     */
    private function seedPayroll(Collection $users, Collection $projects): void
    {
        $payRateTypes = PayRateType::query()->where('is_active', true)->get();
        $deductions = collect(range(1, 5))
            ->map(fn (): Deduction => DeductionFactory::new()->create());

        $employees = $users->where('is_admin', false)->take(12)->values();

        $profiles = $employees->map(function (User $employee) use ($payRateTypes, $projects, $deductions): PayrollEmployeeProfile {
            $employeeNumber = 'EMP-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $employee->id), -6));

            $profile = PayrollEmployeeProfile::query()->updateOrCreate(
                ['user_id' => $employee->id],
                PayrollEmployeeProfileFactory::new()->for($employee)->make([
                    'employee_number' => $employeeNumber,
                ])->toArray()
            );

            $payRateTypes->each(function (PayRateType $type) use ($profile, $employee, $projects): void {
                $projectId = $type->key === 'standard' ? null : $projects->random()->id;

                $activeRateQuery = PayRate::query()
                    ->where('payroll_employee_profile_id', $profile->id)
                    ->where('pay_rate_type_id', $type->id)
                    ->whereNull('expiration_date');

                if ($projectId === null) {
                    $activeRateQuery->whereNull('project_id');
                } else {
                    $activeRateQuery->where('project_id', $projectId);
                }

                $activeRate = $activeRateQuery->first();

                if ($activeRate !== null) {
                    $activeRate->update([
                        'rate_amount' => fake()->randomFloat(4, 22, 85),
                        'approved_by' => $employee->id,
                        'effective_date' => now()->subMonths(2)->toDateString(),
                    ]);

                    return;
                }

                PayRate::query()->create([
                    'payroll_employee_profile_id' => $profile->id,
                    'pay_rate_type_id' => $type->id,
                    'project_id' => $projectId,
                    'effective_date' => now()->subMonths(2)->toDateString(),
                    'rate_amount' => fake()->randomFloat(4, 22, 85),
                    'approved_by' => $employee->id,
                ]);
            });

            $deductions->random(rand(1, 3))->each(function (Deduction $deduction) use ($profile): void {
                EmployeeDeduction::query()->firstOrCreate(
                    [
                        'payroll_employee_profile_id' => $profile->id,
                        'deduction_id' => $deduction->id,
                    ],
                    [
                        'override_amount' => fake()->randomFloat(4, 1, 35),
                        'effective_date' => now()->subMonths(rand(1, 10))->toDateString(),
                        'end_date' => null,
                        'status' => 'active',
                    ]
                );
            });

            return $profile;
        });

        collect(range(0, 2))->each(function (int $offset) use ($users, $profiles): void {
            $periodStart = now()->copy()->subWeeks(($offset + 1) * 2)->startOfWeek();
            $periodEnd = $periodStart->copy()->addDays(6);

            $payRun = PayRun::query()->firstOrCreate(
                [
                    'pay_period_start' => $periodStart->toDateString(),
                    'pay_period_end' => $periodEnd->toDateString(),
                ],
                [
                    'pay_date' => $periodEnd->copy()->addDays(5)->toDateString(),
                    'status' => collect([PayRunStatus::Draft, PayRunStatus::Preview, PayRunStatus::Approved])->random(),
                    'total_gross' => 0,
                    'total_net' => 0,
                    'total_taxes' => 0,
                    'employee_count' => $profiles->count(),
                    'created_by' => $users->random()->id,
                    'approved_by' => $users->random()->id,
                    'finalized_at' => null,
                ]
            );

            $grossTotal = 0.0;
            $taxTotal = 0.0;
            $netTotal = 0.0;

            $profiles->each(function (PayrollEmployeeProfile $profile) use ($payRun, &$grossTotal, &$taxTotal, &$netTotal): void {
                $regularHours = fake()->randomFloat(2, 30, 40);
                $otHours = fake()->randomFloat(2, 0, 8);
                $dtHours = fake()->randomFloat(2, 0, 2);
                $grossPay = round(($regularHours * 38) + ($otHours * 57) + ($dtHours * 76), 2);
                $federalTax = round($grossPay * 0.12, 2);
                $stateTax = round($grossPay * 0.035, 2);
                $socialSecurity = round($grossPay * 0.062, 2);
                $medicare = round($grossPay * 0.0145, 2);
                $otherDeductions = round($grossPay * 0.03, 2);
                $totalTaxesAndDeductions = $federalTax + $stateTax + $socialSecurity + $medicare + $otherDeductions;
                $netPay = round($grossPay - $totalTaxesAndDeductions, 2);

                PayrollStatement::query()->updateOrCreate(
                    [
                        'pay_run_id' => $payRun->id,
                        'payroll_employee_profile_id' => $profile->id,
                    ],
                    array_merge(
                        PayrollStatementFactory::new()->for($profile)->for($profile->user)->make()->toArray(),
                        [
                            'user_id' => $profile->user_id,
                            'total_regular_hours' => $regularHours,
                            'total_ot_hours' => $otHours,
                            'total_dt_hours' => $dtHours,
                            'gross_pay' => $grossPay,
                            'federal_tax' => $federalTax,
                            'state_tax' => $stateTax,
                            'local_tax' => 0,
                            'social_security' => $socialSecurity,
                            'medicare' => $medicare,
                            'other_deductions' => $otherDeductions,
                            'net_pay' => $netPay,
                            'ytd_gross' => round($grossPay * rand(2, 6), 2),
                            'ytd_federal_tax' => round($federalTax * rand(2, 6), 2),
                            'ytd_net' => round($netPay * rand(2, 6), 2),
                        ]
                    )
                );

                $grossTotal += $grossPay;
                $taxTotal += $totalTaxesAndDeductions;
                $netTotal += $netPay;
            });

            $payRun->update([
                'total_gross' => round($grossTotal, 2),
                'total_taxes' => round($taxTotal, 2),
                'total_net' => round($netTotal, 2),
                'employee_count' => $profiles->count(),
            ]);
        });
    }
}
