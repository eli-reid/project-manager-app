<?php

namespace App\Core\Cpanel\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreatesMailboxAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => is_string($this->input('username')) ? trim($this->input('username')) : $this->input('username'),
            'password' => is_string($this->input('password')) ? trim($this->input('password')) : $this->input('password'),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'],
            'password' => ['required', 'string', 'min:12'],
            'quota' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
