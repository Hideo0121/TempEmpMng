<?php

namespace App\Http\Requests;

use App\Models\CandidateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChangeCandidateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status_code' => [
                'required',
                'string',
                Rule::exists('candidate_statuses', 'code')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'decided_job' => [
                'nullable',
                'integer',
                Rule::exists('job_categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'status_code' => 'ステータス',
            'decided_job' => '就業する職種',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $status = $this->input('status_code');
            $decidedJob = $this->input('decided_job');

            if (CandidateStatus::isEmployed((string) $status) && !$decidedJob) {
                $validator->errors()->add('decided_job', '就業する職種を選択してください。');
            }

            if (!CandidateStatus::isEmployed((string) $status) && $decidedJob) {
                $validator->errors()->add('decided_job', '就業決定以外のステータスでは就業する職種を指定できません。');
            }
        });
    }
}
