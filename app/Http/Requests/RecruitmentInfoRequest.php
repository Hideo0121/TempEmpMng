<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecruitmentInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        return [
            'planned_hires' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'recruitment_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $planned = $this->input('planned_hires');
        $comment = $this->input('recruitment_comment');
        $comment = is_string($comment) ? trim($comment) : $comment;

        $this->merge([
            'planned_hires' => is_numeric($planned) ? max(0, (int) $planned) : 0,
            'recruitment_comment' => ($comment === null || $comment === '') ? null : $comment,
        ]);
    }
}
