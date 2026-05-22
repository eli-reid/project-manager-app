<?php

namespace App\Core\Auth\User\Livewire\Admin\Users;

use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\User\Actions\Admin\CreateInvitedUser;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Projects\Models\Project;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core-user::layouts.access-admin')]
#[Title('User Form')]
class Form extends Component
{
    use AuthorizesRequests;

    protected CreateInvitedUser $createInvitedUser;

    public ?User $user = null;

    public ?PayrollEmployeeProfile $payrollProfile = null;

    public bool $isEdit = false;

    public bool $canManagePayrollRates = false;

    public bool $canCreatePayrollProfiles = false;

    public bool $canUpdatePayrollProfiles = false;

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    /**
     * @var array<int, string>
     */
    public array $selectedRoleIds = [];

    public string $new_pay_rate_type_id = '';

    public string $new_project_id = '';

    public string $new_rate_amount = '';

    public string $new_effective_date = '';

    public string $new_expiration_date = '';

    public string $profile_employee_number = '';

    public string $profile_ssn = '';

    public string $profile_date_of_birth = '';

    public string $profile_hire_date = '';

    public string $profile_termination_date = '';

    public string $profile_status = 'active';

    public string $profile_pay_type = 'hourly';

    public string $profile_department = '';

    public string $profile_job_classification = '';

    public string $profile_union_code = '';

    public bool $profile_direct_deposit_active = false;

    public string $profile_sick_hours_allowance = '0.00';

    public string $profile_vacation_hours_allowance = '0.00';

    public function boot(CreateInvitedUser $createInvitedUser): void
    {
        $this->createInvitedUser = $createInvitedUser;
    }

    public function mount(?User $user = null): void
    {
        $this->authorize($user !== null && $user->exists ? 'update' : 'create', $user ?? User::class);

        $currentUser = Auth::user();
        $this->canManagePayrollRates = $currentUser !== null && $currentUser->can('payroll-rates.manage');
        $this->canCreatePayrollProfiles = $currentUser !== null && $currentUser->can('payroll-employees.create');
        $this->canUpdatePayrollProfiles = $currentUser !== null && $currentUser->can('payroll-employees.update');

        if ($user !== null && $user->exists) {
            $user->loadMissing('payrollProfile');

            $this->user = $user;
            $this->isEdit = true;
            $this->first_name = $user->first_name;
            $this->last_name = $user->last_name;
            $this->phone = (string) ($user->phone ?? '');
            $this->username = $user->username;
            $this->email = $user->email;
            $this->is_active = (bool) $user->is_active;
            $this->selectedRoleIds = $user->roles()->pluck('roles.id')->all();
            $this->payrollProfile = $user->payrollProfile;

            if ($this->canManagePayrollRates && $this->payrollProfile !== null) {
                $this->new_effective_date = now()->toDateString();
            }

            if ($this->payrollProfile !== null) {
                $this->seedProfileFormFromExistingProfile();
            } else {
                $this->seedProfileFormDefaults($user);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'is_active' => ['boolean'],
            'selectedRoleIds' => ['required', 'array', 'min:1'],
            'selectedRoleIds.*' => ['exists:roles,id'],
        ];

        if ($this->isEdit) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    public function save(): void
    {
        $this->authorize($this->isEdit ? 'update' : 'create', $this->isEdit ? $this->user : User::class);

        $validated = $this->validate();

        $payload = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => filled($validated['phone'] ?? null) ? $validated['phone'] : null,
            'username' => $validated['username'],
            'email' => $validated['email'],
            'is_active' => (bool) $validated['is_active'],
        ];

        if ($this->isEdit) {
            $user = $this->user;

            if ($user === null) {
                return;
            }

            if (($validated['password'] ?? null) !== null && $validated['password'] !== '') {
                $payload['password'] = $validated['password'];
            }

            $user->update($payload);
        } else {
            $user = $this->createInvitedUser->handle($payload, $validated['selectedRoleIds']);
        }

        if ($this->isEdit) {
            $user->roles()->sync($validated['selectedRoleIds']);
            $user->flushAuthorizationCache();
            User::bumpPermissionCacheVersion();
        }

        session()->flash('success', $this->isEdit
            ? 'User updated successfully.'
            : 'User created and invitation email sent successfully.');

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function addPayRate(): void
    {
        if (! $this->isEdit || $this->user === null) {
            return;
        }

        if (! $this->canManagePayrollRates) {
            abort(403);
        }

        if ($this->payrollProfile === null) {
            $this->addError('pay_rates', 'This user does not have a payroll employee profile yet.');

            return;
        }

        $validated = $this->validate($this->payRateRules());

        $approverId = Auth::id();
        abort_unless(is_string($approverId), 401);

        try {
            PayRate::query()->create([
                'payroll_employee_profile_id' => $this->payrollProfile->id,
                'pay_rate_type_id' => $validated['new_pay_rate_type_id'],
                'project_id' => $validated['new_project_id'] !== '' ? $validated['new_project_id'] : null,
                'rate_amount' => $validated['new_rate_amount'],
                'effective_date' => $validated['new_effective_date'],
                'expiration_date' => ($validated['new_expiration_date'] ?? '') !== '' ? $validated['new_expiration_date'] : null,
                'approved_by' => $approverId,
            ]);
        } catch (DomainException $exception) {
            $this->addError('new_rate_amount', $exception->getMessage());

            return;
        }

        $this->resetNewPayRateForm();

        session()->flash('success', 'Pay rate added successfully.');
    }

    public function createPayrollProfile(): void
    {
        if (! $this->isEdit || $this->user === null) {
            return;
        }

        if (! $this->canCreatePayrollProfiles) {
            abort(403);
        }

        if ($this->payrollProfile !== null) {
            $this->addError('payroll_profile', 'This user already has a payroll profile.');

            return;
        }

        $validated = $this->validate($this->payrollProfileRules());

        $this->payrollProfile = PayrollEmployeeProfile::query()->create([
            'user_id' => $this->user->id,
            'employee_number' => $validated['profile_employee_number'],
            'ssn_encrypted' => $validated['profile_ssn'],
            'date_of_birth' => $validated['profile_date_of_birth'],
            'hire_date' => $validated['profile_hire_date'],
            'termination_date' => ($validated['profile_termination_date'] ?? '') !== '' ? $validated['profile_termination_date'] : null,
            'status' => $validated['profile_status'],
            'pay_type' => $validated['profile_pay_type'],
            'department' => ($validated['profile_department'] ?? '') !== '' ? $validated['profile_department'] : null,
            'job_classification' => $validated['profile_job_classification'],
            'union_code' => ($validated['profile_union_code'] ?? '') !== '' ? $validated['profile_union_code'] : null,
            'direct_deposit_active' => (bool) $validated['profile_direct_deposit_active'],
            'sick_hours_allowance' => (float) $validated['profile_sick_hours_allowance'],
            'vacation_hours_allowance' => (float) $validated['profile_vacation_hours_allowance'],
        ]);

        if ($this->canManagePayrollRates) {
            $this->new_effective_date = now()->toDateString();
        }

        session()->flash('success', 'Payroll profile created successfully.');
    }

    public function updatedProfileSsn(string $value): void
    {
        $this->profile_ssn = $this->formatSsn($value);
    }

    public function updatePayrollProfile(): void
    {
        if (! $this->isEdit || $this->user === null || $this->payrollProfile === null) {
            return;
        }

        if (! $this->canUpdatePayrollProfiles) {
            abort(403);
        }

        $validated = $this->validate($this->payrollProfileRules(isUpdate: true));

        $payload = [
            'employee_number' => $validated['profile_employee_number'],
            'date_of_birth' => $validated['profile_date_of_birth'],
            'hire_date' => $validated['profile_hire_date'],
            'termination_date' => ($validated['profile_termination_date'] ?? '') !== '' ? $validated['profile_termination_date'] : null,
            'status' => $validated['profile_status'],
            'pay_type' => $validated['profile_pay_type'],
            'department' => ($validated['profile_department'] ?? '') !== '' ? $validated['profile_department'] : null,
            'job_classification' => $validated['profile_job_classification'],
            'union_code' => ($validated['profile_union_code'] ?? '') !== '' ? $validated['profile_union_code'] : null,
            'direct_deposit_active' => (bool) $validated['profile_direct_deposit_active'],
            'sick_hours_allowance' => (float) $validated['profile_sick_hours_allowance'],
            'vacation_hours_allowance' => (float) $validated['profile_vacation_hours_allowance'],
        ];

        if (($validated['profile_ssn'] ?? '') !== '') {
            $payload['ssn_encrypted'] = $validated['profile_ssn'];
        }

        $this->payrollProfile->update($payload);
        $this->payrollProfile->refresh();
        $this->seedProfileFormFromExistingProfile();

        session()->flash('success', 'Payroll profile updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function payRateRules(): array
    {
        return [
            'new_pay_rate_type_id' => ['required', 'exists:pay_rate_types,id'],
            'new_project_id' => ['nullable', 'exists:projects,id'],
            'new_rate_amount' => ['required', 'numeric', 'min:0'],
            'new_effective_date' => ['required', 'date'],
            'new_expiration_date' => ['nullable', 'date', 'after_or_equal:new_effective_date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payrollProfileRules(bool $isUpdate = false): array
    {
        $employeeNumberRules = ['required', 'string', 'max:20', 'unique:payroll_employee_profiles,employee_number'];

        if ($isUpdate && $this->payrollProfile !== null) {
            $employeeNumberRules = [
                'required',
                'string',
                'max:20',
                Rule::unique('payroll_employee_profiles', 'employee_number')->ignore($this->payrollProfile->id),
            ];
        }

        $ssnRules = ['string', 'regex:/^\d{3}-\d{2}-\d{4}$/'];

        if ($isUpdate || ! $this->isSsnRequired()) {
            array_unshift($ssnRules, 'nullable');
        } else {
            array_unshift($ssnRules, 'required');
        }

        return [
            'profile_employee_number' => $employeeNumberRules,
            'profile_ssn' => $ssnRules,
            'profile_date_of_birth' => ['required', 'date'],
            'profile_hire_date' => ['required', 'date'],
            'profile_termination_date' => ['nullable', 'date', 'after_or_equal:profile_hire_date'],
            'profile_status' => ['required', 'in:active,inactive,terminated'],
            'profile_pay_type' => ['required', 'in:hourly,salary'],
            'profile_department' => ['nullable', 'string', 'max:255'],
            'profile_job_classification' => ['required', 'string', 'max:255'],
            'profile_union_code' => ['nullable', 'string', 'max:20'],
            'profile_direct_deposit_active' => ['boolean'],
            'profile_sick_hours_allowance' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'profile_vacation_hours_allowance' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }

    protected function resetNewPayRateForm(): void
    {
        $this->new_pay_rate_type_id = '';
        $this->new_project_id = '';
        $this->new_rate_amount = '';
        $this->new_effective_date = now()->toDateString();
        $this->new_expiration_date = '';
    }

    protected function seedProfileFormDefaults(User $user): void
    {
        $this->profile_employee_number = '';
        $this->profile_ssn = '';
        $this->profile_date_of_birth = '';
        $this->profile_hire_date = now()->toDateString();
        $this->profile_termination_date = '';
        $this->profile_status = 'active';
        $this->profile_pay_type = 'hourly';
        $this->profile_department = '';
        $this->profile_job_classification = '';
        $this->profile_union_code = '';
        $this->profile_direct_deposit_active = false;
        $this->profile_sick_hours_allowance = '0.00';
        $this->profile_vacation_hours_allowance = '0.00';

        if ($user->id !== '') {
            $this->profile_employee_number = 'EMP-'.strtoupper(substr($user->id, -6));
        }
    }

    protected function seedProfileFormFromExistingProfile(): void
    {
        if ($this->payrollProfile === null) {
            return;
        }

        $this->profile_employee_number = (string) $this->payrollProfile->employee_number;
        $this->profile_ssn = '';
        $this->profile_date_of_birth = (string) optional($this->payrollProfile->date_of_birth)->toDateString();
        $this->profile_hire_date = (string) optional($this->payrollProfile->hire_date)->toDateString();
        $this->profile_termination_date = (string) optional($this->payrollProfile->termination_date)->toDateString();
        $this->profile_status = (string) $this->payrollProfile->status;
        $this->profile_pay_type = (string) $this->payrollProfile->pay_type;
        $this->profile_department = (string) ($this->payrollProfile->department ?? '');
        $this->profile_job_classification = (string) $this->payrollProfile->job_classification;
        $this->profile_union_code = (string) ($this->payrollProfile->union_code ?? '');
        $this->profile_direct_deposit_active = (bool) $this->payrollProfile->direct_deposit_active;
        $this->profile_sick_hours_allowance = number_format((float) ($this->payrollProfile->sick_hours_allowance ?? 0), 2, '.', '');
        $this->profile_vacation_hours_allowance = number_format((float) ($this->payrollProfile->vacation_hours_allowance ?? 0), 2, '.', '');
    }

    protected function isSsnRequired(): bool
    {
        $raw = Settings::get('payroll.employee_profile.ssn_required', 'true')->raw();

        if (is_bool($raw)) {
            return $raw;
        }

        $normalized = strtolower(trim((string) $raw));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    protected function formatSsn(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        $digits = substr($digits, 0, 9);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) <= 3) {
            return $digits;
        }

        if (strlen($digits) <= 5) {
            return substr($digits, 0, 3).'-'.substr($digits, 3);
        }

        return substr($digits, 0, 3).'-'.substr($digits, 3, 2).'-'.substr($digits, 5);
    }

    public function render()
    {
        $roles = Role::query()->where('is_active', true)->orderBy('name')->get();

        $payRates = collect();
        $payRateTypes = collect();
        $projects = collect();

        if ($this->isEdit && $this->canManagePayrollRates) {
            $payRateTypes = PayRateType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']);

            $projects = Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'project_number']);

            if ($this->payrollProfile !== null) {
                $payRates = PayRate::query()
                    ->with(['payRateType:id,name', 'project:id,name,project_number', 'approver:id,first_name,last_name'])
                    ->where('payroll_employee_profile_id', $this->payrollProfile->id)
                    ->orderByDesc('effective_date')
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        return view('auth-user::livewire.admin.users.form', [
            'roles' => $roles,
            'payRates' => $payRates,
            'payRateTypes' => $payRateTypes,
            'projects' => $projects,
        ])->title($this->isEdit ? 'Edit User' : 'Create User');
    }
}
