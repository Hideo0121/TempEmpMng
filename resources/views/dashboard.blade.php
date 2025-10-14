@extends('layouts.app')

@section('pageTitle', 'トップメニュー')
@section('pageDescription', '紹介者登録・管理・マスタ設定の主要メニューはこちらからアクセスします。')

@section('content')
    <section class="grid gap-6 md:grid-cols-3">
        <article class="flex flex-col justify-between rounded-3xl bg-white p-6 shadow-lg transition hover:-translate-y-1 hover:shadow-xl">
            <div>
                <div class="flex items-center gap-3 text-sm font-medium text-blue-600">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-xl">📝</span>
                    <span>STEP 1</span>
                </div>
                <h2 class="mt-4 text-xl font-semibold text-slate-900">紹介者登録</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">スキルシートの添付、希望職種の選択、見学候補日の入力が行えます。</p>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">ショートカット</p>
                    <p class="text-sm text-slate-700">最新の入力状況を確認</p>
                </div>
                <a href="{{ route('candidates.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">新規登録</a>
            </div>
        </article>

        <article class="flex flex-col justify-between rounded-3xl bg-white p-6 shadow-lg transition hover:-translate-y-1 hover:shadow-xl">
            <div>
                <div class="flex items-center gap-3 text-sm font-medium text-blue-600">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-xl">📋</span>
                    <span>STEP 2</span>
                </div>
                <h2 class="mt-4 text-xl font-semibold text-slate-900">紹介者一覧</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">未閲覧バッジ、ステータス、見学スケジュールをまとめてチェックできます。</p>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">ステータス</p>
                    <p class="text-sm text-slate-700">本日（{{ $today->format('Y/m/d') }}）予定の見学: <span class="font-semibold text-blue-600">{{ number_format($todayVisitCount) }}件</span></p>
                </div>
                <a href="{{ route('candidates.index') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">一覧を見る</a>
            </div>
        </article>

        <article class="flex flex-col justify-between rounded-3xl bg-white p-6 shadow-lg transition hover:-translate-y-1 hover:shadow-xl">
            <div>
                <div class="flex items-center gap-3 text-sm font-medium text-blue-600">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-xl">🗂️</span>
                    <span>STEP 3</span>
                </div>
                <h2 class="mt-4 text-xl font-semibold text-slate-900">マスタ管理</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">希望職種・ステータス・派遣元を最新情報に更新できます（管理者のみ）。</p>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">権限</p>
                    <p class="text-sm text-slate-700">管理者専用メニュー</p>
                </div>
                @if (auth()->user()?->isManager())
                    <a href="{{ route('masters.index') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">マスタ管理</a>
                @else
                    <span class="rounded-xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-500">権限なし</span>
                @endif
            </div>
        </article>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <article class="rounded-3xl bg-white p-6 shadow-lg">
            <header class="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">希望職種 × ステータス集計</h3>
                    <p class="mt-1 text-xs text-slate-500">候補者の希望職種（第1〜第3希望）ごとのステータス件数。</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">総計 {{ number_format($wishJobGrandTotal) }} 件</span>
            </header>
            @php
                $displayJobCategories = $jobCategories->filter(fn ($job) => ($wishJobRowTotals[$job->id] ?? 0) > 0);
                $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            @endphp
            @if ($displayJobCategories->isEmpty())
                <p class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    集計対象のデータがありません。
                </p>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">希望職種</th>
                                @foreach ($statuses as $status)
                                    <th class="px-4 py-3 text-right whitespace-nowrap">{{ $status->label }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-right">合計</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($displayJobCategories as $jobCategory)
                                <tr class="hover:bg-slate-50">
                                    <th scope="row" class="px-4 py-3 text-left font-semibold text-slate-700">{{ $jobCategory->name }}</th>
                                    @foreach ($statuses as $status)
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($wishJobMatrix[$jobCategory->id][$status->code] ?? 0) }}</td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($wishJobRowTotals[$jobCategory->id] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-100 text-xs font-semibold text-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left">合計</th>
                                @foreach ($statuses as $status)
                                    <th class="px-4 py-3 text-right">{{ number_format($wishJobColumnTotals[$status->code] ?? 0) }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-right">{{ number_format($wishJobGrandTotal) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
            <p class="mt-4 text-xs text-slate-400">※ 第1〜第3希望のいずれかに登録された職種を集計対象としています。「就業決定」列は確定した「就業する職種」の件数を表示します。</p>
        </article>

        <article class="rounded-3xl bg-white p-6 shadow-lg">
            <header class="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">対応者 × 見学確定日集計（7日間）</h3>
                    <p class="mt-1 text-xs text-slate-500">本日から7日間の見学確定件数を担当者別に集計。</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">総計 {{ number_format($handlerGrandTotal) }} 件</span>
            </header>
            @php
                $dateKeys = $dateRange->map->toDateString();
                $displayHandlers = $handlers->filter(fn ($handler) => ($handlerRowTotals[$handler->id] ?? 0) > 0);
                $rangeStart = optional($dateRange->first())->toDateString();
                $rangeEnd = optional($dateRange->last())->toDateString();
            @endphp
            @if ($displayHandlers->isEmpty())
                <p class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    集計対象のデータがありません。
                </p>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">対応者</th>
                                @foreach ($dateRange as $date)
                                    <th class="px-4 py-3 text-right whitespace-nowrap">{{ $date->format('m/d') }}（{{ $weekdays[$date->dayOfWeek] }}）</th>
                                @endforeach
                                <th class="px-4 py-3 text-right">合計</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($displayHandlers as $handler)
                                <tr class="hover:bg-slate-50">
                                    <th scope="row" class="px-4 py-3 text-left font-semibold text-slate-700">{{ $handler->name }}</th>
                                    @foreach ($dateKeys as $dateKey)
                                        @php
                                            $cellCount = (int) ($handlerVisitMatrix[$handler->id][$dateKey] ?? 0);
                                            $cellUrl = $cellCount > 0
                                                ? route('candidates.index', [
                                                    'handler' => $handler->id,
                                                    'interview_from' => $dateKey,
                                                    'interview_to' => $dateKey,
                                                ])
                                                : null;
                                        @endphp
                                        <td class="px-4 py-3 text-right text-slate-700">
                                            @if ($cellUrl)
                                                <a href="{{ $cellUrl }}" class="font-semibold text-blue-600 hover:text-blue-500 hover:underline">{{ number_format($cellCount) }}</a>
                                            @else
                                                <span class="text-slate-400">{{ number_format($cellCount) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @php
                                        $rowTotal = (int) ($handlerRowTotals[$handler->id] ?? 0);
                                        $rowUrl = $rowTotal > 0 && $rangeStart && $rangeEnd
                                            ? route('candidates.index', [
                                                'handler' => $handler->id,
                                                'interview_from' => $rangeStart,
                                                'interview_to' => $rangeEnd,
                                            ])
                                            : null;
                                    @endphp
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        @if ($rowUrl)
                                            <a href="{{ $rowUrl }}" class="text-blue-600 hover:text-blue-500 hover:underline">{{ number_format($rowTotal) }}</a>
                                        @else
                                            <span class="text-slate-500">{{ number_format($rowTotal) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-100 text-xs font-semibold text-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left">合計</th>
                                @foreach ($dateKeys as $dateKey)
                                    <th class="px-4 py-3 text-right">{{ number_format($visitDateTotals[$dateKey] ?? 0) }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-right">{{ number_format($handlerGrandTotal) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
            <p class="mt-4 text-xs text-slate-400">※ 対応者の第1・第2担当に登録されているユーザーを集計対象とし、重複する担当者は1件としてカウントしています。</p>
        </article>

        <article class="rounded-3xl bg-white p-6 shadow-lg lg:col-span-2">
            <header class="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">就業開始 × 職種集計（今後）</h3>
                    <p class="mt-1 text-xs text-slate-500">就業決定済み候補者の就業開始予定件数を職種別に表示します。開始予定のない日は表示しません。</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">総計 {{ number_format($employmentStartGrandTotal) }} 件</span>
            </header>
            @php
                $employmentStartDateKeys = $employmentStartDates->map->toDateString();
                $employmentStartDisplayCategories = $employmentStartCategories->filter(fn ($category) => ($employmentStartRowTotals[$category->id] ?? 0) > 0);
            @endphp
            @if ($employmentStartDateKeys->isEmpty() || $employmentStartDisplayCategories->isEmpty())
                <p class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    今後の就業開始予定データがありません。
                </p>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">職種</th>
                                @foreach ($employmentStartDates as $date)
                                    <th class="px-4 py-3 text-right whitespace-nowrap">{{ $date->format('m/d') }}（{{ $weekdays[$date->dayOfWeek] ?? '' }}）</th>
                                @endforeach
                                <th class="px-4 py-3 text-right">合計</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($employmentStartDisplayCategories as $jobCategory)
                                <tr class="hover:bg-slate-50">
                                    <th scope="row" class="px-4 py-3 text-left font-semibold text-slate-700">{{ $jobCategory->name }}</th>
                                    @foreach ($employmentStartDateKeys as $dateKey)
                                        <td class="px-4 py-3 text-right text-slate-700">
                                            {{ number_format($employmentStartMatrix[$jobCategory->id][$dateKey] ?? 0) }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($employmentStartRowTotals[$jobCategory->id] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-100 text-xs font-semibold text-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left">合計</th>
                                @foreach ($employmentStartDateKeys as $dateKey)
                                    <th class="px-4 py-3 text-right">{{ number_format($employmentStartColumnTotals[$dateKey] ?? 0) }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-right">{{ number_format($employmentStartGrandTotal) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
            <p class="mt-4 text-xs text-slate-400">※ 就業開始日は本日以降の予定のみ集計しています。</p>
        </article>
    </section>
@endsection
