<?php

namespace App\Core\Auth\User\Livewire\Admin\Users;

use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\User\Actions\Admin\CreateInvitedUser;
use App\Core\Identity\Models\User;
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

#[Layout('core-user::layouts.user-admin')]
#[Title('User Form')]
class Form extends Component
{
    use AuthorizesRequests;

    protected CreateInvitedUser $createInvitedUser;

    public ?User $user = null;

    public ?PayrollEmployeeProfile $payrollProfile = null;

    public bool $isEdit = false;

    public bool $canManagePayrollRates = false;

    public string $first_name = '';

    public string $last_name = '';

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

    public function boot(CreateInvitedUser $createInvitedUser): void
    {
        $this->createInvitedUser = $createInvitedUser;
    }

    public function mount(?User $user = null): void
    {
        $this->authorize($user !== null && $user->exists ? 'update' : 'create', $user ?? User::class);

        $currentUser = Auth::user();
        $this->canManagePayrollRates = $currentUser !== null && $currentUser->can('payroll-rates.manage');

        if ($user !== null && $user->exists) {
            $user->loadMissing('payrollProfile');

            $this->user = $user;
            $this->isEdit = true;
            $this->first_name = $user->first_name;
            $this->last_name = $user->last_name;
            $this->username = $user->username;
            $this->email = $user->email;
            $this->is_active = (bool) $user->is_active;
            $this->selectedRoleIds = $user->roles()->pluck('roles.id')->all();
            $this->payrollProfile = $user->payrollProfile;

            if ($this->canManagePayrollRates && $this->payrollProfile !== null) {
                $this->new_effective_date = now()->toDateString();
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

    protected function resetNewPayRateForm(): void
    {
        $this->new_pay_rate_type_id = '';
        $this->new_project_id = '';
        $this->new_rate_amount = '';
        $this->new_effective_date = now()->toDateString();
        $this->new_expiration_date = '';
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
