<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CandidateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        $status = $this->route('candidate_status');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('candidate_statuses', 'code')->ignore($status?->code, 'code'),
            ],
            'label' => ['required', 'string', 'max:50'],
            'color_code' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'is_employed_state' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        $color = $this->input('color_code');
        $sortOrder = $this->input('sort_order');

        $this->merge([
            'code' => $code !== null ? Str::snake(trim($code)) : null,
            'label' => $this->input('label') !== null ? trim($this->input('label')) : null,
            'color_code' => $color ? Str::upper($color) : null,
            'sort_order' => is_numeric($sortOrder) ? (int) $sortOrder : 0,
            'is_active' => $this->boolean('is_active'),
            'is_employed_state' => $this->boolean('is_employed_state'),
        ]);
    }
}
