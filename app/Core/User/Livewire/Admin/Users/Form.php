<?php

namespace App\Core\User\Livewire\Admin\Users;

use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
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
        $passwordRules = $this->isEdit
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'password' => $passwordRules,
            'is_active' => ['boolean'],
            'selectedRoleIds' => ['required', 'array', 'min:1'],
            'selectedRoleIds.*' => ['exists:roles,id'],
        ];
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

        if ($validated['password'] !== null && $validated['password'] !== '') {
            $payload['password'] = $validated['password'];
        }

        if ($this->isEdit) {
            $user = $this->user;

            if ($user === null) {
                return;
            }

            $user->update($payload);
        } else {
            $payload['password_change_required'] = true;
            $payload['is_admin'] = false;
            $payload['is_built_in'] = false;
            $user = User::query()->create($payload);
        }

        $user->roles()->sync($validated['selectedRoleIds']);

        session()->flash('success', $this->isEdit ? 'User updated successfully.' : 'User created successfully.');

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function render()
    {
        $roles = Role::query()->where('is_active', true)->orderBy('name')->get();

        return view('core-user::livewire.admin.users.form', [
            'roles' => $roles,
        ])->title($this->isEdit ? 'Edit User' : 'Create User');
    }
}
