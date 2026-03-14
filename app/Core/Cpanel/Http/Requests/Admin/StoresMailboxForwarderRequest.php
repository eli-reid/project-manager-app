<?php

namespace App\Core\Cpanel\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoresMailboxForwarderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->route('email')) ? trim($this->route('email')) : $this->route('email'),
            'forward_to' => is_string($this->input('forward_to')) ? trim($this->input('forward_to')) : $this->input('forward_to'),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'forward_to' => ['required', 'email:rfc', 'max:255', 'different:email'],
        ];
    }
}
