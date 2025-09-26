@php
    $mode = $mode ?? 'create';
    $formAction = $formAction ?? '#';
    $submitLabel = $mode === 'edit' ? '更新する' : '登録する';
    $draftLabel = $mode === 'edit' ? '下書き保存' : '下書き保存';
    $titleSuffix = $mode === 'edit' ? '（編集）' : '（新規）';
    $candidate = $candidate ?? null;
    $agencies = $agencies ?? collect();
    $jobCategories = $jobCategories ?? collect();
    $handlers = $handlers ?? collect();
    $candidateStatuses = $candidateStatuses ?? collect();
    $defaultStatusCode = optional($candidateStatuses->first())->code;
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
@endphp

<form class="space-y-6" method="post" action="{{ $formAction }}" enctype="multipart/form-data">
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
                <input id="name" name="name" type="text" placeholder="例）山田 太郎"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label for="name_kana" class="block text-sm font-semibold text-slate-700">氏名（カナ）<span class="ml-1 text-red-500">*</span></label>
                <input id="name_kana" name="name_kana" type="text" placeholder="例）ヤマダ タロウ"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
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
            </div>
            <div>
                <label for="introduced_on" class="block text-sm font-semibold text-slate-700">紹介日<span class="ml-1 text-red-500">*</span></label>
                <input id="introduced_on" name="introduced_on" type="date"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @for ($i = 1; $i <= 3; $i++)
                <div>
                    <label class="block text-sm font-semibold text-slate-700">第{{ $i }}希望職種</label>
                    <select name="wish_job{{ $i }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">選択してください</option>
                        @foreach ($jobCategories as $jobCategory)
                            <option value="{{ $jobCategory->id }}" @selected((string) $selectedWishJobs[$i] === (string) $jobCategory->id)>
                                {{ $jobCategory->name }}
                            </option>
                        @endforeach
                    </select>
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
                        <input type="date" name="visit_candidate{{ $i }}_date"
                            class="w-1/2 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <input type="time" name="visit_candidate{{ $i }}_time"
                            class="w-1/2 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
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
            </div>
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
                    <input id="transport_day" name="transport_day" type="number" min="0" step="1"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <span class="text-sm text-slate-500">円</span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="transport_month">交通費（月額）</label>
                <div class="mt-1 flex items-center gap-2">
                    <input id="transport_month" name="transport_month" type="number" min="0" step="1"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <span class="text-sm text-slate-500">円</span>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-slate-700" for="other_conditions">その他条件</label>
            <textarea id="other_conditions" name="other_conditions" rows="4" placeholder="例）週3日稼働希望、PC貸与必須など"
                class="mt-1 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
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
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700" for="status_changed_on">状態変化日</label>
                <input id="status_changed_on" name="status_changed_on" type="date"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-slate-700" for="introduction_note">紹介文</label>
            <textarea id="introduction_note" name="introduction_note" rows="4" placeholder="派遣会社からの紹介コメントを貼り付けてください。"
                class="mt-1 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
            <button type="button" class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-blue-600 hover:underline">入力欄を追加</button>
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
            <label for="skill_sheets" class="group flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-blue-400 hover:bg-blue-50">
                <span class="text-3xl">📄</span>
                <span class="mt-3 text-sm font-semibold text-slate-700">ここにPDFをドラッグ＆ドロップ</span>
                <span class="mt-1 text-xs text-slate-500">またはクリックしてファイルを選択</span>
                <input id="skill_sheets" name="skill_sheets[]" type="file" accept="application/pdf" multiple class="hidden">
            </label>

            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between text-sm text-slate-700">
                        <span>skillsheet_yamada.pdf</span>
                        <span class="text-xs text-slate-400">2.4MB · 2025-09-20 09:12</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-200">
                        <div class="h-2 rounded-full bg-blue-500" style="width: 100%"></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between text-sm text-slate-700">
                        <span>portfolio_hanako.pdf</span>
                        <span class="text-xs text-amber-500">アップロード待機中</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-200">
                        <div class="h-2 rounded-full bg-amber-400" style="width: 35%"></div>
                    </div>
                    <p class="mt-2 text-xs text-amber-500">残り 5MB / エラーなし</p>
                </div>
            </div>
        </div>
    </section>

    <div class="flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル / メニューに戻る</a>
        <div class="flex items-center gap-3">
            <button type="button" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">{{ $draftLabel }}</button>
            <button type="submit" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">{{ $submitLabel }}</button>
        </div>
    </div>
</form>
