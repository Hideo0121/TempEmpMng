<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->getKey();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => ['required', Rule::in(['manager', 'staff'])],
            'is_active' => ['required', 'boolean'],
            'password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'min:8',
                'max:191',
                'confirmed',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $role = $this->input('role');

        $this->merge([
            'role' => $role ? strtolower((string) $role) : null,
            'is_active' => $this->boolean('is_active'),
            'password' => $this->filled('password') ? (string) $this->input('password') : null,
        ]);
    }
}
