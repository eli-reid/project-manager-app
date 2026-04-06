<?php

namespace App\Core\Auth\User\Livewire\Admin\Users;

use App\Core\Auth\Role\Models\Role;
use App\Core\Auth\User\Actions\Admin\CreateInvitedUser;
use App\Core\Identity\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core-user::layouts.user-admin')]
#[Title('User Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?User $user = null;

    public bool $isEdit = false;

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

    public function mount(?User $user = null): void
    {
        $this->authorize($user !== null && $user->exists ? 'update' : 'create', $user ?? User::class);

        if ($user !== null && $user->exists) {
            $this->user = $user;
            $this->isEdit = true;
            $this->first_name = $user->first_name;
            $this->last_name = $user->last_name;
            $this->username = $user->username;
            $this->email = $user->email;
            $this->is_active = (bool) $user->is_active;
            $this->selectedRoleIds = $user->roles()->pluck('roles.id')->all();
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
            $user = app(CreateInvitedUser::class)->handle($payload, $validated['selectedRoleIds']);
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

    public function render()
    {
        $roles = Role::query()->where('is_active', true)->orderBy('name')->get();

        return view('auth-user::livewire.admin.users.form', [
            'roles' => $roles,
        ])->title($this->isEdit ? 'Edit User' : 'Create User');
    }
}
