@php
    $mode = $mode ?? 'create';
    $formAction = $formAction ?? '#';
    $httpMethod = strtoupper($httpMethod ?? 'POST');
    $submitLabel = $mode === 'edit' ? '更新する' : '登録する';
    $draftLabel = $mode === 'edit' ? '下書き保存' : '下書き保存';
    $titleSuffix = $mode === 'edit' ? '（編集）' : '（新規）';
    $candidate = $candidate ?? null;
    $agencies = $agencies ?? collect();
    $jobCategories = $jobCategories ?? collect();
    $handlers = $handlers ?? collect();
    $candidateStatuses = $candidateStatuses ?? collect();
    $defaultStatusCode = optional($candidateStatuses->first())->code;
    $backUrl = $backUrl ?? null;
    $selectedAgencyId = old('agency_id', optional($candidate)->agency_id);
    $selectedWishJobs = [
        1 => old('wish_job1', optional($candidate)->wish_job1_id),
        2 => old('wish_job2', optional($candidate)->wish_job2_id),
        3 => old('wish_job3', optional($candidate)->wish_job3_id),
    ];
    $selectedHandlers = [
        1 => old('handler1', optional($candidate)->handler1_user_id),
        2 => old('handler2', optional($candidate)->handler2_user_id),
    ];
    $selectedStatusCode = old('status', optional($candidate)->status_code ?? $defaultStatusCode);
    $decidedJobId = old('decided_job', optional($candidate)->decided_job_category_id);
    $employedStatusCodes = collect($employedStatusCodes ?? \App\Models\CandidateStatus::employedCodes())
        ->map(fn ($code) => mb_strtolower((string) $code))
        ->filter()
        ->values()
        ->all();
    $decidedJobVisible = \App\Models\CandidateStatus::isEmployed((string) $selectedStatusCode);
    $today = now()->format('Y-m-d');
    $visitDateValues = [
        1 => old('visit_candidate1_date', optional(optional($candidate)->visit_candidate1_at)->format('Y-m-d')),
        2 => old('visit_candidate2_date', optional(optional($candidate)->visit_candidate2_at)->format('Y-m-d')),
        3 => old('visit_candidate3_date', optional(optional($candidate)->visit_candidate3_at)->format('Y-m-d')),
    ];
    $visitTimeValues = [
        1 => old('visit_candidate1_time', optional(optional($candidate)->visit_candidate1_at)->format('H:i')),
        2 => old('visit_candidate2_time', optional(optional($candidate)->visit_candidate2_at)->format('H:i')),
        3 => old('visit_candidate3_time', optional(optional($candidate)->visit_candidate3_at)->format('H:i')),
    ];
    $employmentStartDate = old('employment_start_date', optional(optional($candidate)->employment_start_at)->format('Y-m-d'));
    $employmentStartTime = old('employment_start_time', optional(optional($candidate)->employment_start_at)->format('H:i'));
    $assignmentWorkerCodeA = old('assignment_worker_code_a', optional($candidate)->assignment_worker_code_a);
    $assignmentWorkerCodeB = old('assignment_worker_code_b', optional($candidate)->assignment_worker_code_b);
    $assignmentLocker = old('assignment_locker', optional($candidate)->assignment_locker);
    $skillSheets = collect($skillSheets ?? ($candidate ? $candidate->skillSheets : []));
    $existingSkillSheetCount = $skillSheets->count();
    $confirmedInterview = $confirmedInterview ?? null;
    $confirmedScheduledAt = optional($confirmedInterview)->scheduled_at;
    $confirmedVisitDate = old('visit_confirmed_date', optional($confirmedScheduledAt)->format('Y-m-d'));
    $confirmedVisitTime = old('visit_confirmed_time', optional($confirmedScheduledAt)->format('H:i'));
    $remind30mOld = old('remind_30m_enabled');
    $remind30mEnabled = $remind30mOld !== null ? filter_var($remind30mOld, FILTER_VALIDATE_BOOLEAN) : (optional($confirmedInterview)->remind_30m_enabled ?? true);
    $formatFileSize = static function (?int $bytes): string {
        if (empty($bytes)) {
            return '0KB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . 'MB';
        }

        return number_format($bytes / 1024, 0) . 'KB';
    };
    $formatTimestamp = static function ($dateTime): string {
        if (!$dateTime) {
            return '';
        }

        if ($dateTime instanceof \DateTimeInterface) {
            return $dateTime->format('Y-m-d H:i');
        }

        return \Illuminate\Support\Carbon::parse($dateTime)->format('Y-m-d H:i');
    };
@endphp

<form class="space-y-6" method="post" action="{{ $formAction }}" enctype="multipart/form-data" data-candidate-form>
    @csrf
    <input type="hidden" name="notify_handlers" value="0" data-notify-input>
    @if ($httpMethod !== 'POST')
        @method($httpMethod)
    @endif
    @if ($backUrl)
        <input type="hidden" name="back" value="{{ $backUrl }}">
    @endif
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">基本情報</h2>
                <p class="text-sm text-slate-500">氏名・派遣会社・希望職種などの必須項目です。</p>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">STEP 1</span>
        </header>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">氏名<span class="ml-1 text-red-500">*</span></label>
                <input id="name" name="name" type="text" placeholder="例）山田 太郎" value="{{ old('name', optional($candidate)->name) }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="name_kana" class="block text-sm font-semibold text-slate-700">氏名（カナ）<span class="ml-1 text-red-500">*</span></label>
                <input id="name_kana" name="name_kana" type="text" placeholder="例）ヤマダ タロウ" value="{{ old('name_kana', optional($candidate)->name_kana) }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('name_kana')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="agency_id" class="block text-sm font-semibold text-slate-700">派遣会社<span class="ml-1 text-red-500">*</span></label>
                <select id="agency_id" name="agency_id"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">選択してください</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected((string) $selectedAgencyId === (string) $agency->id)>
                            {{ $agency->name }}
                        </option>
                    @endforeach
                </select>
                @error('agency_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="introduced_on" class="block text-sm font-semibold text-slate-700">紹介日<span class="ml-1 text-red-500">*</span></label>
                <input id="introduced_on" name="introduced_on" type="date" value="{{ old('introduced_on', optional(optional($candidate)->introduced_on)->format('Y-m-d') ?? $today) }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('introduced_on')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @for ($i = 1; $i <= 3; $i++)
                <div>
                    <label class="block text-sm font-semibold text-slate-700">第{{ $i }}希望職種@if ($i === 1)<span class="ml-1 text-red-500">*</span>@endif</label>
                    <select name="wish_job{{ $i }}" @if ($i === 1) required @endif
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">選択してください</option>
                        @foreach ($jobCategories as $jobCategory)
                            <option value="{{ $jobCategory->id }}" @selected((string) $selectedWishJobs[$i] === (string) $jobCategory->id)>
                                {{ $jobCategory->name }}
                            </option>
                        @endforeach
                    </select>
                    @error("wish_job{$i}")
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endfor
        </div>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">職場見学候補と対応者</h2>
                <p class="text-sm text-slate-500">候補日を最大3件、対応者を2名まで設定できます。</p>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">STEP 2</span>
        </header>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            @for ($i = 1; $i <= 3; $i++)
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-700">見学候補 {{ $i }}</p>
                    <div class="mt-2 flex gap-2">
                        <input type="date" name="visit_candidate{{ $i }}_date" value="{{ $visitDateValues[$i] }}"
                            class="w-1/2 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <input type="time" name="visit_candidate{{ $i }}_time" value="{{ $visitTimeValues[$i] }}"
                            class="w-1/2 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    @error("visit_candidate{$i}_date")
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error("visit_candidate{$i}_time")
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endfor
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-slate-700">職場見学対応者 1<span class="ml-1 text-red-500">*</span></label>
                <select name="handler1"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">選択してください</option>
                    @foreach ($handlers as $handler)
                        <option value="{{ $handler->id }}" @selected((string) $selectedHandlers[1] === (string) $handler->id)>
                            {{ $handler->name }}
                        </option>
                    @endforeach
                </select>
                @error('handler1')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">職場見学対応者 2</label>
                <select name="handler2"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">未設定</option>
                    @foreach ($handlers as $handler)
                        <option value="{{ $handler->id }}" @selected((string) $selectedHandlers[2] === (string) $handler->id)>
                            {{ $handler->name }}
                        </option>
                    @endforeach
                </select>
                @error('handler2')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 p-5">
            <p class="text-sm font-semibold text-slate-700">見学確定日時</p>
            <p class="mt-1 text-xs text-slate-500">候補日から正式決定した日時を入力してください。空欄の場合は確定日程なしとして扱います。</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label for="visit_confirmed_date" class="block text-xs font-semibold text-slate-600">確定日</label>
                    <input type="date" id="visit_confirmed_date" name="visit_confirmed_date" value="{{ $confirmedVisitDate }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('visit_confirmed_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="visit_confirmed_time" class="block text-xs font-semibold text-slate-600">確定時間</label>
                    <input type="time" id="visit_confirmed_time" name="visit_confirmed_time" value="{{ $confirmedVisitTime }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('visit_confirmed_time')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="remind_30m_enabled" value="0">
                    <input type="checkbox" id="remind_30m_enabled" name="remind_30m_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked($remind30mEnabled)>
                    <label for="remind_30m_enabled">30分前リマインドを送信する</label>
                </div>
                <p class="text-xs text-slate-500">リマインドをOFFにすると 30 分前メールは送信されません。他のリマインドには影響しません。</p>
            </div>
            @error('remind_30m_enabled')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">条件・メモ</h2>
                <p class="text-sm text-slate-500">交通費やその他条件、ステータス、紹介文を入力します。</p>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">STEP 3</span>
        </header>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="transport_day">交通費（日額）</label>
                <div class="mt-1 flex items-center gap-2">
                    <input id="transport_day" name="transport_day" type="number" min="0" step="1" value="{{ old('transport_day', optional($candidate)->transport_cost_day) }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <span class="text-sm text-slate-500">円</span>
                </div>
                @error('transport_day')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="transport_month">交通費（月額）</label>
                <div class="mt-1 flex items-center gap-2">
                    <input id="transport_month" name="transport_month" type="number" min="0" step="1" value="{{ old('transport_month', optional($candidate)->transport_cost_month) }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <span class="text-sm text-slate-500">円</span>
                </div>
                @error('transport_month')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-slate-700" for="other_conditions">その他条件</label>
            <textarea id="other_conditions" name="other_conditions" rows="4" placeholder="例）週3日稼働希望、PC貸与必須など"
                class="mt-1 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('other_conditions', optional($candidate)->other_conditions) }}</textarea>
            @error('other_conditions')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="status">ステータス<span class="ml-1 text-red-500">*</span></label>
                <select id="status" name="status"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @foreach ($candidateStatuses as $status)
                        <option value="{{ $status->code }}" @selected((string) $selectedStatusCode === (string) $status->code)>
                            {{ $status->label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="status_changed_on">状態変化日</label>
                <input id="status_changed_on" name="status_changed_on" type="date" value="{{ old('status_changed_on', optional(optional($candidate)->status_changed_on)->format('Y-m-d')) }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('status_changed_on')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="employment_start_date">就業開始予定</label>
                <div class="mt-1 flex gap-2">
                    <input id="employment_start_date" name="employment_start_date" type="date" value="{{ $employmentStartDate }}"
                        class="w-1/2 rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <input id="employment_start_time" name="employment_start_time" type="time" value="{{ $employmentStartTime }}"
                        class="w-1/2 rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <p class="mt-1 text-xs text-slate-500">開始時間を入力する場合は日付も指定してください。</p>
                @error('employment_start_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('employment_start_time')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="assignment_locker">配属ロッカー</label>
                <input id="assignment_locker" name="assignment_locker" type="text" value="{{ $assignmentLocker }}"
                    placeholder="例）3F-12"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('assignment_locker')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-slate-700">アサイン管理コード</label>
            <p class="mt-1 text-xs text-slate-500">派遣元が発行する作業者コードなどを入力します。</p>
            <div class="mt-3 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-500" for="assignment_worker_code_a">コード A</label>
                    <input id="assignment_worker_code_a" name="assignment_worker_code_a" type="text" value="{{ $assignmentWorkerCodeA }}"
                        placeholder="例）WK-001"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('assignment_worker_code_a')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500" for="assignment_worker_code_b">コード B</label>
                    <input id="assignment_worker_code_b" name="assignment_worker_code_b" type="text" value="{{ $assignmentWorkerCodeB }}"
                        placeholder="例）STF-2024"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('assignment_worker_code_b')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

    <div class="mt-6" data-decided-job-wrapper data-status-employed='@json($employedStatusCodes)'>
            <label class="block text-sm font-semibold text-slate-700" for="decided_job">就業する職種<span class="ml-1 text-xs font-normal text-slate-500">（就業決定時のみ必須）</span></label>
            <select id="decided_job" name="decided_job" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" @disabled(! $decidedJobVisible)>
                <option value="">選択してください</option>
                @foreach ($jobCategories as $jobCategory)
                    <option value="{{ $jobCategory->id }}" @selected((string) $decidedJobId === (string) $jobCategory->id)>
                        {{ $jobCategory->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">ステータスが「就業決定」のときに確定した就業職種を選択します。</p>
            @error('decided_job')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-slate-700" for="introduction_note">紹介文</label>
            <textarea id="introduction_note" name="introduction_note" rows="4" placeholder="派遣会社からの紹介コメントを貼り付けてください。"
                class="mt-1 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('introduction_note', optional($candidate)->introduction_note) }}</textarea>
            <button type="button" class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-blue-600 hover:underline">入力欄を追加</button>
            @error('introduction_note')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">スキルシート添付</h2>
                <p class="text-sm text-slate-500">PDF ファイルをドラッグ＆ドロップ、またはクリックで選択します。（最大 5 件、10MB/件）</p>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">STEP 4</span>
        </header>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <label for="skill_sheets"
                class="group flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-blue-400 hover:bg-blue-50"
                data-skill-dropzone
                data-skill-existing-count="{{ $existingSkillSheetCount }}">
                <span class="text-3xl">📄</span>
                <span class="mt-3 text-sm font-semibold text-slate-700">ここにPDFをドラッグ＆ドロップ</span>
                <span class="mt-1 text-xs text-slate-500">またはクリックしてファイルを選択</span>
                <input id="skill_sheets" name="skill_sheets[]" type="file" accept="application/pdf" multiple class="hidden" data-skill-input>
            </label>

            <div>
                <p data-skill-feedback class="mt-2 hidden text-xs font-semibold"></p>

                <div data-skill-pending-section class="mt-4 hidden space-y-3 rounded-2xl border border-blue-200 bg-white p-4">
                    <div class="flex items-center justify-between text-xs font-semibold text-blue-700">
                        <span>アップロード予定のファイル</span>
                        <button type="button" data-skill-clear class="rounded-full border border-blue-200 px-2 py-1 text-[11px] font-semibold text-blue-600 transition hover:bg-blue-50">すべて取り消す</button>
                    </div>
                    <div data-skill-pending-list class="space-y-3"></div>
                </div>

                @php
                    $skillSheetErrors = collect($errors->get('skill_sheets'))->merge(
                        collect($errors->get('skill_sheets.*'))->flatten()
                    );
                @endphp
                @if ($skillSheetErrors->isNotEmpty())
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-600">
                        <ul class="space-y-1">
                            @foreach ($skillSheetErrors as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-4 space-y-3" data-skill-existing-list>
                    @forelse ($skillSheets as $sheet)
                        @php
                            $sizeLabel = $formatFileSize($sheet->size_bytes ?? null);
                            $uploadedLabel = $formatTimestamp($sheet->updated_at ?? $sheet->created_at);
                        @endphp
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-3 text-sm text-slate-700">
                                <span class="truncate" title="{{ $sheet->original_name }}">{{ $sheet->original_name }}</span>
                                <span class="shrink-0 text-xs text-slate-400">
                                    {{ $sizeLabel }}@if($uploadedLabel) · {{ $uploadedLabel }}@endif
                                </span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-slate-200" aria-hidden="true">
                                <div class="h-2 rounded-full bg-blue-500" style="width: 100%"></div>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1 font-semibold text-blue-600">
                                    <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                                    アップロード済み
                                </span>
                                @if(!empty($sheet->note))
                                    <span class="ml-3 truncate text-slate-400" title="{{ $sheet->note }}">{{ $sheet->note }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div data-skill-empty-placeholder class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                            まだアップロードされたスキルシートはありません。
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div class="flex items-center justify-between">
    <a href="{{ $backUrl ?? ($mode === 'edit' ? route('candidates.index') : route('dashboard')) }}" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル / メニューに戻る</a>
        <div class="flex items-center gap-3">
            <button type="button" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">{{ $draftLabel }}</button>
            <button type="submit" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">{{ $submitLabel }}</button>
        </div>
    </div>
</form>

<div class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" data-mail-confirm-modal>
    <div data-mail-confirm-overlay class="absolute inset-0 bg-slate-900/60"></div>
    <div class="relative z-10 w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">メール送信の確認</h2>
                <p class="mt-1 text-sm text-slate-500">登録・更新内容を保存するときに、職場見学対応者へメール通知を送信しますか？</p>
            </div>
            <button type="button" aria-label="閉じる" data-mail-confirm-close class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">&times;</button>
        </div>
        <div class="mt-6 space-y-3 text-sm text-slate-600">
            <p>「送信して保存」を選ぶと、設定されている対応者へ確認メールを送信します。</p>
            <p>メールを送信したくない場合は「送信せず保存」を選択してください。</p>
        </div>
        <div class="mt-8 flex flex-col gap-3 text-sm font-semibold sm:flex-row sm:items-center sm:justify-center">
            <button type="button" data-mail-confirm-dismiss class="rounded-xl border border-slate-200 px-5 py-2 text-slate-500 transition hover:bg-slate-100">キャンセル</button>
            <button type="button" data-mail-confirm-skip class="rounded-xl border border-slate-300 px-5 py-2 text-slate-600 transition hover:bg-slate-50">送信せず保存</button>
            <button type="button" data-mail-confirm-send class="rounded-xl bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-500">送信して保存</button>
        </div>
    </div>

<script>
    const initDecidedJobToggle = () => {
        const statusSelect = document.getElementById('status');
        const decidedWrapper = document.querySelector('[data-decided-job-wrapper]');

        if (!statusSelect || !decidedWrapper) {
            return;
        }

        const employedCodes = (() => {
            if (!decidedWrapper) {
                return [];
            }

            try {
                const raw = decidedWrapper.dataset.statusEmployed || '[]';
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    return parsed.map((code) => String(code).toLowerCase());
                }
            } catch (error) {
                console.warn('Failed to parse employed status codes', error);
            }

            return [];
        })();
        const decidedSelect = decidedWrapper.querySelector('select');

        const updateState = () => {
            const isEmployed = employedCodes.includes((statusSelect.value || '').toLowerCase());

            if (decidedSelect) {
                decidedSelect.disabled = !isEmployed;
                decidedSelect.required = isEmployed;

                if (!isEmployed) {
                    decidedSelect.value = '';
                }
            }
        };

        statusSelect.addEventListener('change', updateState);
        updateState();
    };

    const initHandlerNotificationPrompt = () => {
        const form = document.querySelector('[data-candidate-form]');

        if (!form) {
            return;
        }

        const notifyInput = form.querySelector('[data-notify-input]');

        if (!notifyInput) {
            return;
        }

        const handlerSelects = [
            form.querySelector('select[name="handler1"]'),
            form.querySelector('select[name="handler2"]'),
        ];

        const modal = document.querySelector('[data-mail-confirm-modal]');
        const overlay = modal ? modal.querySelector('[data-mail-confirm-overlay]') : null;
        const sendButton = modal ? modal.querySelector('[data-mail-confirm-send]') : null;
        const skipButton = modal ? modal.querySelector('[data-mail-confirm-skip]') : null;
        const dismissButton = modal ? modal.querySelector('[data-mail-confirm-dismiss]') : null;
        const closeButton = modal ? modal.querySelector('[data-mail-confirm-close]') : null;

        if (!modal || !overlay || !sendButton || !skipButton || !dismissButton || !closeButton) {
            return;
        }

        let submissionConfirmed = false;
        let pendingSubmit = null;

        const closeModal = ({ resetPending = true } = {}) => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';

            if (resetPending) {
                pendingSubmit = null;
            }
        };

        const openModal = (onSubmit) => {
            pendingSubmit = onSubmit;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            sendButton.focus({ preventScroll: true });
        };

        sendButton.addEventListener('click', () => {
            const submitAction = pendingSubmit;

            notifyInput.value = '1';
            closeModal({ resetPending: false });

            if (submitAction) {
                submissionConfirmed = true;
                submitAction();
            }

            pendingSubmit = null;
        });

        skipButton.addEventListener('click', () => {
            const submitAction = pendingSubmit;

            notifyInput.value = '0';
            closeModal({ resetPending: false });

            if (submitAction) {
                submissionConfirmed = true;
                submitAction();
            }

            pendingSubmit = null;
        });

        const cancelModal = () => {
            closeModal();
        };

        closeButton.addEventListener('click', cancelModal);
        dismissButton.addEventListener('click', cancelModal);
        overlay.addEventListener('click', cancelModal);
        document.addEventListener('keydown', (event) => {
            if (!modal.classList.contains('hidden') && event.key === 'Escape') {
                cancelModal();
            }
        });

        form.addEventListener('submit', (event) => {
            if (submissionConfirmed) {
                submissionConfirmed = false;
                return;
            }

            const hasHandlers = handlerSelects.some((select) => select && select.value);

            if (!hasHandlers) {
                notifyInput.value = '0';
                submissionConfirmed = true;
                return;
            }

            event.preventDefault();

            openModal(() => form.submit());
        });
    };

    const initCandidateFormEnhancements = () => {
        initDecidedJobToggle();
        initHandlerNotificationPrompt();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCandidateFormEnhancements);
    } else {
        initCandidateFormEnhancements();
    }
</script>
