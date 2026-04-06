<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Core\WeatherApi\Contracts\WeatherApiContract;
use App\Domains\Addresses\Models\Address;
use App\Domains\Dailies\Livewire\User\Dailies\Form;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Projects\Models\Project;
use Carbon\CarbonInterface;
use Livewire\Livewire;

it('creates a daily report draft', function (): void {
    $user = userWithDailiesPermissions(['dailies.create', 'dailies.view', 'dailies.edit', 'dailies.submit']);
    $project = Project::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('report_date', '2026-03-20')
        ->set('project_id', (string) $project->id)
        ->set('custom_project_name', null)
        ->set('total_regular_hours', '99')
        ->set('total_overtime_hours', '88')
        ->set('work_performed', [
            ['description' => 'Site prep and forms', 'hours' => '3', 'employees' => ['Alice Worker', 'Bob Operator'], 'is_overtime' => false],
            ['description' => 'Concrete finishing', 'hours' => '2.5', 'employees' => ['Bob Operator'], 'is_overtime' => true],
        ])
        ->set('onsite_employees', ['Alice Worker', 'Bob Operator'])
        ->set('weather_condition', 'Cloudy')
        ->set('temperature', '61.2')
        ->set('temperature_unit', 'F')
        ->set('additional_notes', 'Concrete pour and layout checks completed.')
        ->call('saveAsDraft');

    $report = DailyReport::query()->where('user_id', $user->id)->first();

    expect($report)->not()->toBeNull();
    expect($report?->status)->toBe(DailyReport::STATUS_DRAFT);
    expect((float) $report?->total_regular_hours)->toBe(3.0);
    expect((float) $report?->total_overtime_hours)->toBe(2.5);
    expect((float) $report?->total_hours)->toBe(5.5);
    expect($report?->work_performed)->toBe([
        ['description' => 'Site prep and forms', 'hours' => 3, 'employees' => ['Alice Worker', 'Bob Operator'], 'is_overtime' => false],
        ['description' => 'Concrete finishing', 'hours' => 2.5, 'employees' => ['Bob Operator'], 'is_overtime' => true],
    ]);
    expect($report?->onsite_employees)->toBe(['Alice Worker', 'Bob Operator']);
});

it('saves and submits a daily report', function (): void {
    $user = userWithDailiesPermissions(['dailies.create', 'dailies.view', 'dailies.edit', 'dailies.submit']);
    $project = Project::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('report_date', '2026-03-21')
        ->set('project_id', (string) $project->id)
        ->set('total_regular_hours', '7.5')
        ->set('total_overtime_hours', '0.5')
        ->call('saveAndSubmit');

    $report = DailyReport::query()->where('user_id', $user->id)->first();

    expect($report)->not()->toBeNull();
    expect($report?->status)->toBe(DailyReport::STATUS_SUBMITTED);
    expect((string) $report?->submitted_by_id)->toBe((string) $user->id);
});

it('updates an existing rejected daily report', function (): void {
    $user = userWithDailiesPermissions(['dailies.create', 'dailies.view', 'dailies.edit', 'dailies.submit']);
    $project = Project::factory()->create(['is_active' => true]);

    $dailyReport = DailyReport::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => DailyReport::STATUS_REJECTED,
        'report_date' => '2026-03-19',
        'total_regular_hours' => 6,
        'total_overtime_hours' => 0,
        'total_hours' => 6,
    ]);

    Livewire::actingAs($user)
        ->test(Form::class, ['dailyReport' => $dailyReport])
        ->set('work_performed', [
            ['description' => 'Crew framing punch-list', 'hours' => '8', 'employees' => ['Crew Lead'], 'is_overtime' => false],
            ['description' => 'Late inspection fixes', 'hours' => '2', 'employees' => ['Crew Lead'], 'is_overtime' => true],
        ])
        ->set('additional_notes', 'Updated after foreman feedback.')
        ->call('saveAsDraft');

    $dailyReport->refresh();

    expect((float) $dailyReport->total_regular_hours)->toBe(8.0);
    expect((float) $dailyReport->total_overtime_hours)->toBe(2.0);
    expect((float) $dailyReport->total_hours)->toBe(10.0);
    expect($dailyReport->additional_notes)->toBe('Updated after foreman feedback.');
});

it('deletes a draft daily report', function (): void {
    $user = userWithDailiesPermissions(['dailies.view', 'dailies.edit', 'dailies.delete']);

    $dailyReport = DailyReport::factory()->create([
        'user_id' => $user->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    Livewire::actingAs($user)
        ->test(Form::class, ['dailyReport' => $dailyReport])
        ->call('delete');

    expect(DailyReport::withTrashed()->whereKey($dailyReport->id)->first()?->trashed())->toBeTrue();
});

it('requires custom project name when no project is selected', function (): void {
    $user = userWithDailiesPermissions(['dailies.create', 'dailies.view']);

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('project_id', null)
        ->set('custom_project_name', null)
        ->set('report_date', '2026-03-22')
        ->set('total_regular_hours', '8')
        ->set('total_overtime_hours', '0')
        ->call('saveAsDraft')
        ->assertHasErrors(['custom_project_name' => 'required_without']);
});

it('prevents submitting a rejected report until reset to draft', function (): void {
    $user = userWithDailiesPermissions(['dailies.view', 'dailies.edit', 'dailies.submit']);
    $project = Project::factory()->create(['is_active' => true]);

    $dailyReport = DailyReport::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => DailyReport::STATUS_REJECTED,
    ]);

    Livewire::actingAs($user)
        ->test(Form::class, ['dailyReport' => $dailyReport])
        ->set('project_id', (string) $project->id)
        ->set('total_regular_hours', '8')
        ->set('total_overtime_hours', '0')
        ->call('saveAndSubmit')
        ->assertForbidden();
});

it('auto loads weather fields from weather api during save', function (): void {
    $user = userWithDailiesPermissions(['dailies.create', 'dailies.view']);

    $address = Address::factory()->create([
        'address1' => '123 Main St',
        'city' => 'Denver',
        'state' => 'CO',
        'zip' => '80202',
    ]);

    $project = Project::factory()->create([
        'address_id' => $address->id,
        'is_active' => true,
    ]);

    app()->instance(WeatherApiContract::class, new class implements WeatherApiContract
    {
        public function getCurrentWeather(string $location): ?array
        {
            return [
                'location' => ['name' => 'Denver', 'region' => 'Colorado'],
                'current' => [
                    'temp_f' => 52.4,
                    'condition' => ['text' => 'Light Rain'],
                ],
            ];
        }

        public function getHistoricalWeather(string $location, CarbonInterface $date): ?array
        {
            return null;
        }

        public function getForecastWeather(string $location, CarbonInterface $date): ?array
        {
            return null;
        }

        public function getWeatherByIp(string $ipAddress, ?CarbonInterface $date = null): ?array
        {
            return null;
        }

        public function getLocationFromIp(string $ipAddress): ?array
        {
            return null;
        }

        public function extractWeatherForDailyReport(array $weatherData): array
        {
            return [
                'condition' => 'Light Rain',
                'temperature' => 52.4,
                'temperature_unit' => 'F',
                'location_name' => 'Denver, Colorado',
            ];
        }

        public function hasStoredWeatherData(string $location, CarbonInterface $date, string $type = 'forecast'): bool
        {
            return false;
        }
    });

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('report_date', '2026-03-24')
        ->set('project_id', (string) $project->id)
        ->set('total_regular_hours', '8')
        ->set('total_overtime_hours', '0')
        ->call('saveAsDraft');

    $report = DailyReport::query()->where('user_id', $user->id)->latest('created_at')->first();

    expect($report)->not()->toBeNull();
    expect($report?->weather_condition)->toBe('Light Rain');
    expect((float) $report?->temperature)->toBe(52.4);
    expect($report?->temperature_unit)->toBe('F');
});

it('uses weather default location when project address is unavailable', function (): void {
    $user = userWithDailiesPermissions(['dailies.create', 'dailies.view']);

    Settings::set('weatherapi.default_location', 'Phoenix, AZ');

    app()->instance(WeatherApiContract::class, new class implements WeatherApiContract
    {
        public function getCurrentWeather(string $location): ?array
        {
            expect($location)->toBe('Phoenix, AZ');

            return [
                'location' => ['name' => 'Phoenix', 'region' => 'Arizona'],
                'current' => [
                    'temp_f' => 85.0,
                    'condition' => ['text' => 'Sunny'],
                ],
            ];
        }

        public function getHistoricalWeather(string $location, CarbonInterface $date): ?array
        {
            return null;
        }

        public function getForecastWeather(string $location, CarbonInterface $date): ?array
        {
            return null;
        }

        public function getWeatherByIp(string $ipAddress, ?CarbonInterface $date = null): ?array
        {
            return null;
        }

        public function getLocationFromIp(string $ipAddress): ?array
        {
            return null;
        }

        public function extractWeatherForDailyReport(array $weatherData): array
        {
            return [
                'condition' => 'Sunny',
                'temperature' => 85.0,
                'temperature_unit' => 'F',
                'location_name' => 'Phoenix, Arizona',
            ];
        }

        public function hasStoredWeatherData(string $location, CarbonInterface $date, string $type = 'forecast'): bool
        {
            return false;
        }
    });

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('report_date', '2026-03-25')
        ->set('custom_project_name', 'Unassigned Service Call')
        ->set('total_regular_hours', '8')
        ->set('total_overtime_hours', '0')
        ->call('saveAsDraft');

    $report = DailyReport::query()->where('user_id', $user->id)->latest('created_at')->first();

    expect($report)->not()->toBeNull();
    expect($report?->weather_condition)->toBe('Sunny');
    expect((float) $report?->temperature)->toBe(85.0);
});

/**
 * @param  array<int, string>  $permissions
 */
if (! function_exists('userWithDailiesPermissions')) {
    function userWithDailiesPermissions(array $permissions): User
    {
        app(DomainPermissionSynchronizer::class)->sync();

        $user = User::factory()->create(['is_admin' => false]);

        $role = Role::query()->create([
            'name' => 'Dailies Workflow Test Role '.str()->uuid(),
            'description' => 'Role for dailies workflow feature tests',
            'is_active' => true,
            'built_in' => false,
            'access_level' => 20,
        ]);

        $permissionIds = collect($permissions)
            ->map(function (string $permission): ?string {
                [$resource, $action] = explode('.', $permission, 2);

                return Permission::query()
                    ->where('resource', $resource)
                    ->where('action', $action)
                    ->value('id');
            })
            ->filter()
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->sync([$role->id]);

        return $user->fresh();
    }
}
