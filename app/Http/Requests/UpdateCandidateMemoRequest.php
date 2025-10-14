<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'other_conditions' => ['nullable', 'string', 'max:2000'],
            'back' => ['nullable', 'string'],
        ];
    }
}
