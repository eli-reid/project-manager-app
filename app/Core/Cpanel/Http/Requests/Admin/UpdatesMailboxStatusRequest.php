<?php

namespace App\Core\Cpanel\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatesMailboxStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->route('email')) ? trim($this->route('email')) : $this->route('email'),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
        ];
    }
}
