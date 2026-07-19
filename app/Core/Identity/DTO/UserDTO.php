<?php

namespace App\Core\Identity\DTO;

use App\Core\Identity\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<array{id:string,name:string,username:?string,email:?string,is_active:bool,roles:list<string>}>
 */
final readonly class UserDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $username,
        public ?string $email,
        public bool $isActive,
        public array $roles = [],
    ) {}

    public static function fromUser(User $user): self
    {
        $roleNames = $user->relationLoaded('roles')
            ? $user->roles->pluck('name')->values()->all()
            : $user->roles()->pluck('name')->all();

        return new self(
            id: (string) $user->id,
            name: $user->name,
            username: $user->username,
            email: $user->email,
            isActive: (bool) $user->is_active,
            roles: $roleNames,
        );
    }

    /**
     * @return array{id:string,name:string,username:?string,email:?string,is_active:bool,roles:list<string>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'roles' => $this->roles,
        ];
    }

    /**
     * @return array{id:string,name:string,username:?string,email:?string,is_active:bool,roles:list<string>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
