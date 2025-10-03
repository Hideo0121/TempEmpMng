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
            'planned_hires' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'recruitment_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sortOrder = $this->input('sort_order');
        $plannedHires = $this->input('planned_hires');
        $comment = $this->input('recruitment_comment');
        $comment = is_string($comment) ? trim($comment) : $comment;

        $this->merge([
            'sort_order' => is_numeric($sortOrder) ? (int) $sortOrder : 0,
            'is_active' => $this->boolean('is_active'),
            'planned_hires' => is_numeric($plannedHires) ? max(0, (int) $plannedHires) : null,
            'recruitment_comment' => ($comment === null || $comment === '') ? null : $comment,
        ]);
    }
}
