<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('job_categories', 'name')->ignore($this->route('job_category')?->getKey()),
            ],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sortOrder = $this->input('sort_order');

        $this->merge([
            'sort_order' => is_numeric($sortOrder) ? (int) $sortOrder : 0,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
