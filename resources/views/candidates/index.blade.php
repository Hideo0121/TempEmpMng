@extends('layouts.app')

@php
    $jobColumnWidthClass = 'w-72';
    $currentSort = $currentSort ?? null;
    $currentDirection = $currentDirection ?? null;
    $sortDefaults = [
        'viewed' => 'asc',
        'name' => 'asc',
        'agency' => 'asc',
        'wish_job' => 'asc',
        'decided_job' => 'asc',
        'introduced_on' => 'desc',
        'interview_at' => 'asc',
        'status' => 'asc',
        'status_changed_on' => 'desc',
    ];
    $sortLabels = [
        'viewed' => '閲覧状態',
        'name' => '氏名',
        'agency' => '派遣会社',
        'wish_job' => '希望職種',
        'decided_job' => '就業する職種',
        'introduced_on' => '紹介日',
        'interview_at' => '見学確定日時',
        'status' => 'ステータス',
        'status_changed_on' => '状態変化日',
    ];
    $sortUrl = function (string $column) use ($currentSort, $currentDirection, $sortDefaults) {
        $isCurrent = $currentSort === $column;
        $nextDirection = $isCurrent
            ? ($currentDirection === 'asc' ? 'desc' : 'asc')
            : ($sortDefaults[$column] ?? 'asc');

        return request()->fullUrlWithQuery([
            'sort' => $column,
            'direction' => $nextDirection,
            'page' => null,
        ]);
    };
    $ariaSort = function (string $column) use ($currentSort, $currentDirection) {
        if ($currentSort !== $column) {
            return 'none';
        }

        return $currentDirection === 'asc' ? 'ascending' : 'descending';
    };
    $sortDescription = $currentSort
        ? sprintf('%s（%s）', $sortLabels[$currentSort] ?? $currentSort, $currentDirection === 'asc' ? '昇順' : '降順')
        : '未閲覧優先 → 紹介日降順（既定）';
    $perPageOptions = $perPageOptions ?? [10, 25, 50, 100];
    $currentPerPage = (int) ($filters['per_page'] ?? ($perPageOptions[0] ?? 10));
    $namesParameters = request()->except('page');
    $namesUrl = route('candidates.names', $namesParameters, false);
    $multiSelectSummary = static function (array $selected, $options, string $idKey = 'id', string $labelKey = 'name'): string {
        if (empty($selected)) {
            return 'すべて';
        }

        $labels = [];
        foreach ($options as $option) {
            $id = is_object($option) ? $option->{$idKey} : ($option[$idKey] ?? null);
            if ($id === null) {
                continue;
            }

            if (in_array((int) $id, $selected, true)) {
                $labels[] = is_object($option) ? $option->{$labelKey} : ($option[$labelKey] ?? (string) $id);
            }
        }

        if (empty($labels)) {
            return 'すべて';
        }

        return count($labels) <= 2 ? implode('・', $labels) : count($labels) . '件選択';
    };
@endphp

@section('pageTitle', '紹介者一覧')
@section('pageDescription', '未閲覧バッジ・見学スケジュール・ステータスをまとめて確認できます。検索条件は画面上部から絞り込み可能です。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <form class="grid gap-4 lg:grid-cols-12" action="{{ route('candidates.index') }}" method="get">
            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-slate-700" for="keyword">自由語検索</label>
                <input id="keyword" name="keyword" type="text" value="{{ $filters['keyword'] }}"
                    placeholder="氏名・条件・メモなど"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div class="lg:col-span-2">
                <span class="block text-sm font-semibold text-slate-700">派遣会社</span>
                @php
                    $agencySummary = $multiSelectSummary($filters['agency'], $agencies);
                @endphp
                <div class="mt-1 relative" data-multiselect>
                    <button type="button" class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2 text-left text-sm font-medium text-slate-700 shadow-sm transition hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200" data-multiselect-toggle data-placeholder="すべて" aria-haspopup="listbox" aria-expanded="false">
                        <span data-multiselect-summary>{{ $agencySummary }}</span>
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="absolute left-0 right-0 z-30 mt-2 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xl top-full" data-multiselect-panel>
                        <div class="max-h-56 overflow-y-auto py-1">
                            @foreach ($agencies as $agency)
                                <label class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                    <input type="checkbox" name="agency[]" value="{{ $agency->id }}" @checked(in_array($agency->id, $filters['agency'], true)) data-option-label="{{ $agency->name }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $agency->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-200 pt-2 text-right">
                            <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-500" data-multiselect-clear>選択をクリア</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <span class="block text-sm font-semibold text-slate-700">希望職種</span>
                @php
                    $wishJobSummary = $multiSelectSummary($filters['wish_job'], $jobCategories);
                @endphp
                <div class="mt-1 relative" data-multiselect>
                    <button type="button" class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2 text-left text-sm font-medium text-slate-700 shadow-sm transition hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200" data-multiselect-toggle data-placeholder="すべて" aria-haspopup="listbox" aria-expanded="false">
                        <span data-multiselect-summary>{{ $wishJobSummary }}</span>
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="absolute left-0 right-0 z-30 mt-2 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xl top-full" data-multiselect-panel>
                        <div class="max-h-56 overflow-y-auto py-1">
                            @foreach ($jobCategories as $jobCategory)
                                <label class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                    <input type="checkbox" name="wish_job[]" value="{{ $jobCategory->id }}" @checked(in_array($jobCategory->id, $filters['wish_job'], true)) data-option-label="{{ $jobCategory->name }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $jobCategory->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-200 pt-2 text-right">
                            <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-500" data-multiselect-clear>選択をクリア</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <span class="block text-sm font-semibold text-slate-700">就業する職種</span>
                @php
                    $decidedJobSummary = $multiSelectSummary($filters['decided_job'], $jobCategories);
                @endphp
                <div class="mt-1 relative" data-multiselect>
                    <button type="button" class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2 text-left text-sm font-medium text-slate-700 shadow-sm transition hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-200" data-multiselect-toggle data-placeholder="すべて" aria-haspopup="listbox" aria-expanded="false">
                        <span data-multiselect-summary>{{ $decidedJobSummary }}</span>
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="absolute left-0 right-0 z-30 mt-2 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xl top-full" data-multiselect-panel>
                        <div class="max-h-56 overflow-y-auto py-1">
                            @foreach ($jobCategories as $jobCategory)
                                <label class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                    <input type="checkbox" name="decided_job[]" value="{{ $jobCategory->id }}" @checked(in_array($jobCategory->id, $filters['decided_job'], true)) data-option-label="{{ $jobCategory->name }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $jobCategory->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-200 pt-2 text-right">
                            <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-500" data-multiselect-clear>選択をクリア</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-slate-700">ステータス</label>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    @foreach ($statuses as $status)
                        <label class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1">
                            <input type="checkbox" name="status[]" value="{{ $status->code }}"
                                @checked(in_array($status->code, $filters['status'], true))
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>{{ $status->label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">紹介日</label>
                <div class="mt-1 grid grid-cols-2 gap-2">
                    <input type="date" name="introduced_from" value="{{ $filters['introduced_from'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <input type="date" name="introduced_to" value="{{ $filters['introduced_to'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">見学確定日</label>
                <div class="mt-1 grid grid-cols-2 gap-2">
                    <input type="date" name="interview_from" value="{{ $filters['interview_from'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <input type="date" name="interview_to" value="{{ $filters['interview_to'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">就業開始予定</label>
                <div class="mt-1 grid grid-cols-2 gap-2">
                    <input type="date" name="employment_start_from" value="{{ $filters['employment_start_from'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <input type="date" name="employment_start_to" value="{{ $filters['employment_start_to'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">対応者</label>
                <select name="handler" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">すべて</option>
                    @foreach ($handlers as $handler)
                        <option value="{{ $handler->id }}" @selected((string) $filters['handler'] === (string) $handler->id)>{{ $handler->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">アサインコード</label>
                <input type="text" name="assignment_code" value="{{ $filters['assignment_code'] }}" placeholder="部分一致で検索"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">配属ロッカー</label>
                <input type="text" name="assignment_locker" value="{{ $filters['assignment_locker'] }}" placeholder="例）3F-12"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div class="lg:col-span-2 lg:pr-4 xl:pr-6">
                <label class="block text-sm font-semibold text-slate-700">30分前リマインド</label>
                    <div class="mt-2 flex items-center gap-3 text-sm">
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="all" @checked($filters['remind_30m'] === 'all') class="text-blue-600">すべて</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="on" @checked($filters['remind_30m'] === 'on') class="text-blue-600">ONのみ</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="off" @checked($filters['remind_30m'] === 'off') class="text-blue-600">OFFのみ</label>
                </div>
            </div>

            <div class="lg:col-span-2 lg:pl-6 xl:pl-10">
                <label class="block text-sm font-semibold text-slate-700">閲覧状態</label>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm md:flex-nowrap">
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="all" @checked($filters['view_state'] === 'all') class="text-blue-600">すべて</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="unread" @checked($filters['view_state'] === 'unread') class="text-blue-600">未閲覧</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="read" @checked($filters['view_state'] === 'read') class="text-blue-600">閲覧済</label>
                </div>
            </div>

            <div class="lg:col-span-12 flex flex-wrap items-center justify-end gap-3 pt-2">
                <input type="hidden" name="sort" value="{{ $currentSort ?? '' }}">
                <input type="hidden" name="direction" value="{{ $currentDirection ?? '' }}">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                    <span>表示件数</span>
                    <select name="per_page" data-per-page-select class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected((int) $currentPerPage === (int) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="button" data-filter-clear="{{ route('candidates.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">クリア</button>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500" data-reset-sort>検索</button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl bg-white shadow-md">
        <header class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div class="flex items-center gap-3 text-sm text-slate-600">
                <span>{{ $sortDescription }}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <button type="button" class="rounded-full border border-blue-200 px-3 py-1 font-semibold text-blue-600 transition hover:bg-blue-50" data-copy-names data-copy-names-url="{{ $namesUrl }}">氏名コピー</button>
                <a href="{{ route('candidates.export', request()->query()) }}"
                    class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 transition hover:bg-slate-200">CSVエクスポート</a>
            </div>
        </header>

        <div class="overflow-x-auto">
            <table class="min-w-[1500px] w-full divide-y divide-slate-200">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="p-4 text-left" aria-sort="{{ $ariaSort('viewed') }}">
                            <a href="{{ $sortUrl('viewed') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>閲覧</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'viewed')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left" aria-sort="{{ $ariaSort('name') }}">
                            <a href="{{ $sortUrl('name') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>氏名</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'name')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left" aria-sort="{{ $ariaSort('agency') }}">
                            <a href="{{ $sortUrl('agency') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>派遣会社</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'agency')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left {{ $jobColumnWidthClass }}" aria-sort="{{ $ariaSort('wish_job') }}">
                            <a href="{{ $sortUrl('wish_job') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>希望職種</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'wish_job')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left {{ $jobColumnWidthClass }}" aria-sort="{{ $ariaSort('decided_job') }}">
                            <a href="{{ $sortUrl('decided_job') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>就業する職種</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'decided_job')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left" aria-sort="{{ $ariaSort('introduced_on') }}">
                            <a href="{{ $sortUrl('introduced_on') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>紹介日 / 見学確定</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'introduced_on')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left" aria-sort="none">アサイン情報</th>
                        <th class="p-4 text-left whitespace-nowrap" aria-sort="{{ $ariaSort('status') }}">
                            <a href="{{ $sortUrl('status') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>ステータス</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'status')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left" aria-sort="{{ $ariaSort('status_changed_on') }}">
                            <a href="{{ $sortUrl('status_changed_on') }}" class="group inline-flex items-center gap-1 font-semibold text-slate-600 transition hover:text-blue-600">
                                <span>状態変化日</span>
                                <span aria-hidden="true" class="text-[0.7rem] text-slate-300 group-hover:text-slate-400">
                                    @if ($currentSort === 'status_changed_on')
                                        {{ $currentDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th class="p-4 text-left" aria-sort="none">アクション</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-sm">
                    @forelse ($candidates as $candidate)
                        @php
                            $viewRecord = $candidate->views->first();
                            $latestInterview = $candidate->interviews->first();
                            $status = $candidate->status;
                            $jobPreferences = collect([
                                ['label' => '第1希望', 'value' => optional($candidate->wishJob1)->name],
                                ['label' => '第2希望', 'value' => optional($candidate->wishJob2)->name],
                                ['label' => '第3希望', 'value' => optional($candidate->wishJob3)->name],
                            ])->filter(fn ($item) => filled($item['value']));
                            $statusColor = $status?->color_code ?: '#DFE7F3';
                            $statusGradientStart = $statusColor . 'cc';
                            $statusGradientEnd = $statusColor . '99';
                            $statusBorderColor = $statusColor . '80';
                            $statusLabel = $status->label ?? 'ステータス未設定';
                            $employmentStartAt = $candidate->employment_start_at;
                            $interviewAt = optional($latestInterview)->scheduled_at;
                            $assignmentCodes = collect([
                                $candidate->assignment_worker_code_a,
                                $candidate->assignment_worker_code_b,
                            ])->filter();
                            $assignmentCodeLabel = $assignmentCodes->isNotEmpty() ? $assignmentCodes->implode('/') : null;
                        @endphp
                        <tr class="transition hover:bg-slate-50" data-candidate-row="{{ $candidate->id }}" data-candidate-name="{{ $candidate->name }}">
                            <td class="p-4">
                                @if (!$viewRecord)
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white" title="未閲覧">●</span>
                                @else
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600" title="閲覧済">✓</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="font-semibold text-slate-900">{{ $candidate->name }}</div>
                                <div class="text-xs text-slate-500">ID: {{ str_pad((string) $candidate->id, 6, '0', STR_PAD_LEFT) }}</div>
                            </td>
                            <td class="p-4 text-slate-700">{{ optional($candidate->agency)->name ?? '未設定' }}</td>
                            <td class="p-4 align-top {{ $jobColumnWidthClass }}">
                                <div class="flex flex-wrap gap-2 text-xs">
                                    @forelse ($jobPreferences as $index => $job)
                                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ $job['label'] }}: {{ $job['value'] }}</span>
                                    @empty
                                        <span class="text-slate-400">希望職種未設定</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="p-4 align-top text-slate-700 {{ $jobColumnWidthClass }}" data-role="decided-job">
                                @if (\App\Models\CandidateStatus::isEmployed((string) $candidate->status_code))
                                    <span class="inline-flex min-h-[2.25rem] items-center rounded-xl bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">
                                        {{ optional($candidate->decidedJob)->name ?? '未設定' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-slate-700">
                                <div class="flex flex-col leading-tight">
                                    <span class="text-xs font-semibold text-slate-500">紹介日</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ optional($candidate->introduced_on)->format('Y/m/d') ?? '未設定' }}</span>
                                    <span class="mt-3 text-xs font-semibold text-slate-500">見学確定日時</span>
                                    <span class="text-sm text-slate-700">@if ($interviewAt){{ optional($interviewAt)->format('Y/m/d H:i') }}@else未調整@endif</span>
                                </div>
                            </td>
                            <td class="p-4 text-slate-700">
                                @if ($assignmentCodeLabel)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $assignmentCodeLabel }}</span>
                                @else
                                    <span class="text-xs text-slate-400">コード未登録</span>
                                @endif
                                <div class="mt-2 text-[11px] text-slate-500">ロッカー: {{ $candidate->assignment_locker ?? '—' }}</div>
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset"
                                    data-role="status-label"
                                    style="background-image: linear-gradient(135deg, {{ $statusGradientStart }}, {{ $statusGradientEnd }}); border-color: {{ $statusBorderColor }}; --tw-ring-color: {{ $statusBorderColor }};"
                                >
                                    <span class="inline-block h-2 w-2 rounded-full bg-slate-600/60 shadow" aria-hidden="true"></span>
                                    <span data-role="status-text">{{ $statusLabel }}</span>
                                </span>
                                @if (\App\Models\CandidateStatus::isEmployed((string) $candidate->status_code))
                                    <div class="mt-2 text-[11px] text-slate-600">就業開始: {{ optional($employmentStartAt)->format('Y/m/d H:i') ?? '未定' }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-slate-700" data-role="status-changed-on">{{ optional($candidate->status_changed_on)->format('Y/m/d') ?? '—' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <a href="{{ route('candidates.show', ['candidate' => $candidate, 'back' => request()->fullUrl()]) }}" class="rounded-full border border-blue-200 px-3 py-1 text-blue-600 transition hover:bg-blue-50">詳細</a>
                                    @if (auth()->user()?->isManager())
                                        <a href="{{ route('candidates.edit', ['candidate' => $candidate, 'back' => request()->fullUrl()]) }}" class="rounded-full border border-blue-200 px-3 py-1 text-blue-600 transition hover:bg-blue-50">編集</a>
                                    @endif
                                    <button type="button" class="rounded-full border border-amber-200 px-3 py-1 text-amber-600 transition hover:bg-amber-50" data-role="status-open" data-candidate-id="{{ $candidate->id }}" data-candidate-name="{{ $candidate->name }}" data-current-status="{{ $candidate->status_code }}" data-current-decided-job="{{ $candidate->decided_job_category_id }}" data-action="{{ route('candidates.status.update', $candidate) }}">ステータス変更</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-6 text-center text-slate-500">該当する紹介者は見つかりませんでした。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="flex flex-col gap-4 border-t border-slate-200 px-6 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <span>
                全 {{ number_format($candidates->total()) }} 件中
                {{ $candidates->firstItem() }}〜{{ $candidates->lastItem() }} 件を表示
            </span>
            @if ($candidates->hasPages())
                <div class="md:ml-auto">
                    {{ $candidates->onEachSide(1)->links() }}
                </div>
            @endif
        </footer>
    </section>
    <div id="status-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div data-modal-overlay class="absolute inset-0 bg-slate-900/60"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">ステータス変更</h2>
                    <p id="status-modal-candidate-name" class="mt-1 text-sm text-slate-500"></p>
                </div>
                <button type="button" class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="閉じる" data-modal-dismiss>&times;</button>
            </div>
            <form id="status-form" class="mt-6 space-y-4" method="post">
                @csrf
                @method('PATCH')
                <div>
                    <label for="status-modal-select" class="block text-sm font-semibold text-slate-700">新しいステータス</label>
                    <select id="status-modal-select" name="status_code" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" required>
                        <option value="" disabled selected>選択してください</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->code }}">{{ $statusOption->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1" data-modal-decided-job data-status-employed='@json($employedStatusCodes ?? [])'>
                    <label for="status-modal-decided-job" class="block text-sm font-semibold text-slate-700">就業する職種<span class="ml-1 text-xs font-normal text-slate-500">（就業決定時のみ必須）</span></label>
                    <select id="status-modal-decided-job" name="decided_job" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">選択してください</option>
                        @foreach ($jobCategories as $jobCategory)
                            <option value="{{ $jobCategory->id }}">{{ $jobCategory->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500">ステータスが「就業決定」の場合に必ず選択してください。</p>
                </div>
                <div id="status-form-error" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-600"></div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50" data-modal-dismiss>キャンセル</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-500" data-loading-text="更新中...">更新する</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterClearButton = document.querySelector('[data-filter-clear]');
            if (filterClearButton) {
                filterClearButton.addEventListener('click', () => {
                    const url = filterClearButton.dataset.filterClear;
                    const form = filterClearButton.closest('form');
                    if (form) {
                        form.reset();
                    }
                    if (url) {
                        window.location.href = url;
                    }
                });
            }

            const sortInput = document.querySelector('input[name="sort"]');
            const directionInput = document.querySelector('input[name="direction"]');
            const searchButton = document.querySelector('[data-reset-sort]');

            if (searchButton && sortInput && directionInput) {
                searchButton.addEventListener('click', () => {
                    sortInput.value = '';
                    directionInput.value = '';
                });
            }

            const perPageSelect = document.querySelector('[data-per-page-select]');
            if (perPageSelect) {
                perPageSelect.addEventListener('change', () => {
                    const form = perPageSelect.closest('form');
                    if (form) {
                        form.requestSubmit();
                    }
                });
            }

            const wireDateRangeAutoFill = (startSelector, endSelector) => {
                const startInput = document.querySelector(startSelector);
                const endInput = document.querySelector(endSelector);

                if (!startInput || !endInput) {
                    return;
                }

                startInput.addEventListener('change', () => {
                    const value = startInput.value;

                    if (!value) {
                        return;
                    }

                    if (!endInput.value || endInput.value < value) {
                        endInput.value = value;
                    }
                });
            };

            wireDateRangeAutoFill('input[name="introduced_from"]', 'input[name="introduced_to"]');
            wireDateRangeAutoFill('input[name="interview_from"]', 'input[name="interview_to"]');
            wireDateRangeAutoFill('input[name="employment_start_from"]', 'input[name="employment_start_to"]');

            const initMultiSelect = (root) => {
                const toggle = root.querySelector('[data-multiselect-toggle]');
                const panel = root.querySelector('[data-multiselect-panel]');
                const summary = root.querySelector('[data-multiselect-summary]');
                const clearButton = root.querySelector('[data-multiselect-clear]');
                const checkboxes = Array.from(root.querySelectorAll('input[type="checkbox"]'));
                const placeholder = toggle?.dataset.placeholder || 'すべて';

                if (!toggle || !panel || !summary) {
                    return;
                }

                const updateSummary = () => {
                    const checked = checkboxes.filter((checkbox) => checkbox.checked);

                    if (checked.length === 0) {
                        summary.textContent = placeholder;
                        return;
                    }

                    const labels = checked.map((checkbox) => checkbox.dataset.optionLabel || checkbox.value);
                    summary.textContent = labels.length <= 2 ? labels.join('・') : `${labels.length}件選択`;
                };

                const handleDocumentClick = (event) => {
                    if (!root.contains(event.target)) {
                        closePanel();
                    }
                };

                const closePanel = () => {
                    if (panel.classList.contains('hidden')) {
                        return;
                    }

                    panel.classList.add('hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                    document.removeEventListener('click', handleDocumentClick, true);
                };

                const openPanel = () => {
                    if (!panel.classList.contains('hidden')) {
                        return;
                    }

                    panel.classList.remove('hidden');
                    toggle.setAttribute('aria-expanded', 'true');
                    document.addEventListener('click', handleDocumentClick, true);
                };

                toggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (panel.classList.contains('hidden')) {
                        openPanel();
                    } else {
                        closePanel();
                    }
                });

                panel.addEventListener('click', (event) => {
                    event.stopPropagation();
                });

                checkboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        updateSummary();
                    });
                });

                if (clearButton) {
                    clearButton.addEventListener('click', (event) => {
                        event.preventDefault();
                        checkboxes.forEach((checkbox) => {
                            checkbox.checked = false;
                        });
                        updateSummary();
                    });
                }

                updateSummary();

                root.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closePanel();
                    }
                });
            };

            document.querySelectorAll('[data-multiselect]').forEach((root) => initMultiSelect(root));

            const copyNamesButton = document.querySelector('[data-copy-names]');
            if (copyNamesButton) {
                const originalLabel = copyNamesButton.textContent.trim();

                const fallbackCopy = (text) => {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'absolute';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);

                    const selection = document.getSelection();
                    const originalRange = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

                    textarea.select();
                    textarea.setSelectionRange(0, textarea.value.length);

                    let successful = false;
                    try {
                        successful = document.execCommand('copy');
                    } catch (error) {
                        successful = false;
                    }

                    document.body.removeChild(textarea);
                    if (originalRange && selection) {
                        selection.removeAllRanges();
                        selection.addRange(originalRange);
                    }

                    return successful;
                };

                const showFeedback = (message, isError = false) => {
                    copyNamesButton.textContent = message;
                    copyNamesButton.disabled = true;

                    if (isError) {
                        copyNamesButton.classList.add('bg-rose-50', 'text-rose-600', 'border-rose-200');
                    } else {
                        copyNamesButton.classList.remove('bg-rose-50', 'text-rose-600', 'border-rose-200');
                    }

                    setTimeout(() => {
                        copyNamesButton.textContent = originalLabel;
                        copyNamesButton.disabled = false;
                        copyNamesButton.classList.remove('bg-rose-50', 'text-rose-600', 'border-rose-200');
                    }, 2000);
                };

                copyNamesButton.addEventListener('click', async () => {
                    const namesUrl = copyNamesButton.dataset.copyNamesUrl || '';

                    if (!namesUrl) {
                        showFeedback('コピーに失敗しました', true);
                        return;
                    }

                    copyNamesButton.disabled = true;

                    let names = [];

                    try {
                        copyNamesButton.textContent = '取得中...';
                        const response = await fetch(namesUrl, {
                            headers: {
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            throw new Error('Request failed');
                        }

                        const payload = await response.json();
                        const fetchedNames = Array.isArray(payload?.names) ? payload.names : [];
                        names = fetchedNames
                            .map((name) => (typeof name === 'string' ? name.trim() : ''))
                            .filter((name) => name.length > 0);
                    } catch (error) {
                        showFeedback('コピーに失敗しました', true);
                        return;
                    }

                    if (names.length === 0) {
                        showFeedback('コピー対象なし', true);
                        return;
                    }

                    const text = names.join('\n');
                    let success = false;

                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        try {
                            await navigator.clipboard.writeText(text);
                            success = true;
                        } catch (error) {
                            success = false;
                        }
                    }

                    if (!success) {
                        success = fallbackCopy(text);
                    }

                    showFeedback(success ? 'コピーしました' : 'コピーに失敗しました', !success);
                });
            }

            const modal = document.getElementById('status-modal');
            const form = document.getElementById('status-form');
            const select = document.getElementById('status-modal-select');
            const decidedWrapper = document.querySelector('[data-modal-decided-job]');
            const decidedSelect = decidedWrapper ? decidedWrapper.querySelector('select') : null;
            const employedStatusCodes = (() => {
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
            const escapeHtml = (value) => {
                if (value == null) {
                    return '';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };
            const candidateNameEl = document.getElementById('status-modal-candidate-name');
            const errorBox = document.getElementById('status-form-error');
            const dismissButtons = modal.querySelectorAll('[data-modal-dismiss]');
            const overlay = modal.querySelector('[data-modal-overlay]');
            let activeButton = null;
            let activeRow = null;

            const updateDecidedJobState = () => {
                if (!decidedWrapper || !decidedSelect) {
                    return;
                }

                const isEmployed = employedStatusCodes.includes((select.value || '').toLowerCase());
                decidedSelect.disabled = !isEmployed;
                decidedSelect.required = isEmployed;

                if (!isEmployed) {
                    decidedSelect.value = '';
                }
            };

            const openModal = (button) => {
                activeButton = button;
                const candidateId = button.dataset.candidateId;
                activeRow = document.querySelector(`[data-candidate-row="${candidateId}"]`);
                form.setAttribute('action', button.dataset.action);
                select.value = button.dataset.currentStatus || '';
                if (decidedSelect) {
                    const currentDecided = button.dataset.currentDecidedJob || '';
                    decidedSelect.value = currentDecided;
                }
                updateDecidedJobState();
                candidateNameEl.textContent = button.dataset.candidateName || '';
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                select.focus({ preventScroll: true });
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
                activeButton = null;
                activeRow = null;
                form.reset();
                if (decidedSelect) {
                    decidedSelect.value = '';
                }
                updateDecidedJobState();
            };

            document.querySelectorAll('[data-role="status-open"]').forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });

            dismissButtons.forEach((button) => {
                button.addEventListener('click', () => closeModal());
            });

            overlay.addEventListener('click', closeModal);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            select.addEventListener('change', updateDecidedJobState);

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!activeButton || !activeRow) {
                    return;
                }

                errorBox.classList.add('hidden');
                errorBox.textContent = '';

                const submitButton = form.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.textContent = submitButton.dataset.loadingText || '更新中...';

                try {
                    const formData = new FormData(form);
                    formData.set('redirect_to', window.location.href);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        const messages = errorData?.errors ? Object.values(errorData.errors).flat() : [errorData.message || '更新に失敗しました。'];
                        errorBox.textContent = messages.join('\n');
                        errorBox.classList.remove('hidden');
                        return;
                    }

                    const data = await response.json();

                    const statusBadge = activeRow.querySelector('[data-role="status-label"]');
                    const statusDateCell = activeRow.querySelector('[data-role="status-changed-on"]');
                    const decidedJobCell = activeRow.querySelector('[data-role="decided-job"]');

                    if (statusBadge) {
                        const badgeText = statusBadge.querySelector('[data-role="status-text"]');
                        if (badgeText) {
                            badgeText.textContent = data.status_label ?? 'ステータス未設定';
                        } else {
                            statusBadge.textContent = data.status_label ?? 'ステータス未設定';
                        }

                        if (data.status_color) {
                            const baseColor = data.status_color;
                            statusBadge.style.backgroundImage = `linear-gradient(135deg, ${baseColor}cc, ${baseColor}99)`;
                            statusBadge.style.borderColor = `${baseColor}80`;
                            statusBadge.style.setProperty('--tw-ring-color', `${baseColor}80`);
                        }
                    }

                    if (statusDateCell) {
                        statusDateCell.textContent = data.status_changed_on ?? '—';
                    }

                    activeButton.dataset.currentStatus = data.status_code ?? '';
                    activeButton.dataset.currentDecidedJob = data.decided_job_id ?? '';

                    if (decidedJobCell) {
                        const isEmployedStatus = employedStatusCodes.includes((data.status_code || '').toLowerCase());

                        if (isEmployedStatus) {
                            const decidedLabel = escapeHtml(data.decided_job_label ?? '未設定');
                            decidedJobCell.innerHTML = `<span class="inline-flex min-h-[2.25rem] items-center rounded-xl bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">${decidedLabel}</span>`;
                        } else {
                            decidedJobCell.innerHTML = '<span class="text-slate-400">—</span>';
                        }
                    }

                    if (data.celebrate_url) {
                        closeModal();
                        window.location.assign(data.celebrate_url);
                        return;
                    }

                    closeModal();
                } catch (error) {
                    errorBox.textContent = 'ネットワークエラーが発生しました。時間を置いて再度お試しください。';
                    errorBox.classList.remove('hidden');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            });
        });
    </script>
@endsection
