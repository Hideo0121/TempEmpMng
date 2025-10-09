<?php

namespace App\Http\Requests;

use App\Models\CandidateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $candidateId = $this->route('candidate')?->getKey();

        return [
            'name' => ['required', 'string', 'max:120'],
            'name_kana' => ['required', 'string', 'max:120'],
            'agency_id' => [
                'required',
                'integer',
                Rule::exists('agencies', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'introduced_on' => ['required', 'date'],
            'wish_job1' => [
                'required',
                'integer',
                Rule::exists('job_categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'wish_job2' => [
                'nullable',
                'integer',
                Rule::exists('job_categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'wish_job3' => [
                'nullable',
                'integer',
                Rule::exists('job_categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'decided_job' => [
                'nullable',
                'integer',
                Rule::exists('job_categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'visit_candidate1_date' => ['nullable', 'date'],
            'visit_candidate1_time' => ['nullable', 'date_format:H:i'],
            'visit_candidate2_date' => ['nullable', 'date'],
            'visit_candidate2_time' => ['nullable', 'date_format:H:i'],
            'visit_candidate3_date' => ['nullable', 'date'],
            'visit_candidate3_time' => ['nullable', 'date_format:H:i'],
            'visit_confirmed_date' => ['nullable', 'date'],
            'visit_confirmed_time' => ['nullable', 'date_format:H:i'],
            'employment_start_date' => ['nullable', 'date'],
            'employment_start_time' => ['nullable', 'date_format:H:i'],
            'assignment_worker_code_a' => ['nullable', 'string', 'max:50'],
            'assignment_worker_code_b' => ['nullable', 'string', 'max:50'],
            'assignment_locker' => ['nullable', 'string', 'max:50'],
            'remind_30m_enabled' => ['nullable', 'boolean'],
            'handler1' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'handler2' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'transport_day' => ['nullable', 'integer', 'min:0'],
            'transport_month' => ['nullable', 'integer', 'min:0'],
            'other_conditions' => ['nullable', 'string', 'max:2000'],
            'status' => [
                'required',
                'string',
                Rule::exists('candidate_statuses', 'code')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'status_changed_on' => ['nullable', 'date'],
            'introduction_note' => ['nullable', 'string'],
            'skill_sheets' => ['nullable', 'array', 'max:5'],
            'skill_sheets.*' => ['file', 'mimetypes:application/pdf', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'transport_day' => $this->input('transport_day') !== null && $this->input('transport_day') !== ''
                ? (int) $this->input('transport_day')
                : null,
            'transport_month' => $this->input('transport_month') !== null && $this->input('transport_month') !== ''
                ? (int) $this->input('transport_month')
                : null,
            'remind_30m_enabled' => $this->boolean('remind_30m_enabled'),
            'decided_job' => $this->filled('decided_job') ? (int) $this->input('decided_job') : null,
            'assignment_worker_code_a' => $this->filled('assignment_worker_code_a')
                ? trim((string) $this->input('assignment_worker_code_a'))
                : null,
            'assignment_worker_code_b' => $this->filled('assignment_worker_code_b')
                ? trim((string) $this->input('assignment_worker_code_b'))
                : null,
            'assignment_locker' => $this->filled('assignment_locker')
                ? trim((string) $this->input('assignment_locker'))
                : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'skill_sheets.max' => 'スキルシートは最大5件までアップロードできます。',
            'skill_sheets.*.mimetypes' => 'アップロードできるファイル形式は PDF のみです。',
            'skill_sheets.*.max' => 'スキルシートのサイズは 10MB 以内で指定してください。',
            'wish_job1.required' => '第1希望職種を選択してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'wish_job1' => '第1希望職種',
            'decided_job' => '就業する職種',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $wishJobs = collect([
                $this->input('wish_job1'),
                $this->input('wish_job2'),
                $this->input('wish_job3'),
            ])->filter();

            if ($wishJobs->count() !== $wishJobs->unique()->count()) {
                $validator->errors()->add('wish_job1', '希望職種は重複しないよう選択してください。');
            }

            foreach (range(1, 3) as $index) {
                $timeKey = "visit_candidate{$index}_time";
                $dateKey = "visit_candidate{$index}_date";

                if ($this->filled($timeKey) && !$this->filled($dateKey)) {
                    $validator->errors()->add($dateKey, '時間を入力する場合は日付も指定してください。');
                }
            }

            if ($this->filled('visit_confirmed_time') && !$this->filled('visit_confirmed_date')) {
                $validator->errors()->add('visit_confirmed_date', '確定時間を入力する場合は日付も指定してください。');
            }

            if ($this->filled('employment_start_time') && !$this->filled('employment_start_date')) {
                $validator->errors()->add('employment_start_date', '就業開始時間を入力する場合は日付も指定してください。');
            }

            $status = $this->input('status');
            $decidedJob = $this->input('decided_job');

            if (CandidateStatus::isEmployed((string) $status) && !$decidedJob) {
                $validator->errors()->add('decided_job', '就業する職種を選択してください。');
            }

            if (!CandidateStatus::isEmployed((string) $status) && $decidedJob) {
                $validator->errors()->add('decided_job', '就業決定以外のステータスでは就業する職種を設定できません。');
            }
        });
    }
}
