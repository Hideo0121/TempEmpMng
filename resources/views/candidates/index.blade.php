@extends('layouts.app')

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
                <label class="block text-sm font-semibold text-slate-700" for="agency">派遣会社</label>
                <select id="agency" name="agency"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">すべて</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected((string) $filters['agency'] === (string) $agency->id)>{{ $agency->name }}</option>
                    @endforeach
                </select>
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
                <label class="block text-sm font-semibold text-slate-700">見学確定日時</label>
                <div class="mt-1 grid grid-cols-2 gap-2">
                    <input type="datetime-local" name="interview_from" value="{{ $filters['interview_from'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <input type="datetime-local" name="interview_to" value="{{ $filters['interview_to'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
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
                <label class="block text-sm font-semibold text-slate-700">30分前リマインド</label>
                    <div class="mt-2 flex items-center gap-3 text-sm">
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="all" @checked($filters['remind_30m'] === 'all') class="text-blue-600">すべて</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="on" @checked($filters['remind_30m'] === 'on') class="text-blue-600">ONのみ</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="off" @checked($filters['remind_30m'] === 'off') class="text-blue-600">OFFのみ</label>
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">閲覧状態</label>
                    <div class="mt-2 flex items-center gap-3 text-sm">
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="all" @checked($filters['view_state'] === 'all') class="text-blue-600">すべて</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="unread" @checked($filters['view_state'] === 'unread') class="text-blue-600">未閲覧</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="read" @checked($filters['view_state'] === 'read') class="text-blue-600">閲覧済</label>
                </div>
            </div>

            <div class="lg:col-span-12 flex flex-wrap items-center justify-end gap-3 pt-2">
                <button type="reset" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">クリア</button>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">検索</button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl bg-white shadow-md">
        <header class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div class="flex items-center gap-3 text-sm text-slate-600">
                <span>未閲覧優先 → 紹介日降順（既定）</span>
                <button class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">並び替えを変更</button>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <button class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 transition hover:bg-slate-200">CSVエクスポート</button>
                <button class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 transition hover:bg-slate-200">カラム設定</button>
            </div>
        </header>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="p-4 text-left">閲覧</th>
                        <th class="p-4 text-left">氏名</th>
                        <th class="p-4 text-left">派遣会社</th>
                        <th class="p-4 text-left">希望職種</th>
                        <th class="p-4 text-left">紹介日</th>
                        <th class="p-4 text-left">見学確定日時</th>
                        <th class="p-4 text-left">ステータス</th>
                        <th class="p-4 text-left">状態変化日</th>
                        <th class="p-4 text-left">アクション</th>
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
                            $statusColor = $status?->color_code ?? '#DFE7F3';
                        @endphp
                        <tr class="transition hover:bg-slate-50">
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
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2 text-xs">
                                    @forelse ($jobPreferences as $index => $job)
                                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ $job['label'] }}: {{ $job['value'] }}</span>
                                    @empty
                                        <span class="text-slate-400">希望職種未設定</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="p-4 text-slate-700">{{ optional($candidate->introduced_on)->format('Y/m/d') }}</td>
                            <td class="p-4 text-slate-700">{{ optional(optional($latestInterview)->scheduled_at)->format('Y/m/d H:i') ?? '未調整' }}</td>
                            <td class="p-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-slate-700" style="background-color: {{ $statusColor }}">{{ $status->label ?? 'ステータス未設定' }}</span>
                            </td>
                            <td class="p-4 text-slate-700">{{ optional($candidate->status_changed_on)->format('Y/m/d') ?? '—' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <a href="{{ route('candidates.show', $candidate) }}" class="rounded-full border border-blue-200 px-3 py-1 text-blue-600 transition hover:bg-blue-50">詳細</a>
                                    <a href="{{ route('candidates.edit', $candidate) }}" class="rounded-full border border-blue-200 px-3 py-1 text-blue-600 transition hover:bg-blue-50">編集</a>
                                    <button type="button" class="rounded-full border border-amber-200 px-3 py-1 text-amber-600 transition hover:bg-amber-50">ステータス変更</button>
                                    <button type="button" class="rounded-full border border-emerald-200 px-3 py-1 text-emerald-600 transition hover:bg-emerald-50">見学確定</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-slate-500">該当する紹介者は見つかりませんでした。</td>
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
@endsection
