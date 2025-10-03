@extends('layouts.app')

@section('pageTitle', '募集情報マスタ')
@section('pageDescription', '希望職種ごとの募集人数とコメントを最新化し、外部公開ページと整合させます。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">募集情報一覧</h2>
                <p class="text-sm text-slate-500">希望職種に紐づく募集枠とコメントを管理します。残り枠は「募集人数 − 決定人数」で算出しています。</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('masters.job-categories.index') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">希望職種マスタ</a>
                <a href="{{ route('masters.candidate-statuses.index') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">ステータスマスタ</a>
                <a href="{{ route('masters.agencies.index') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">派遣会社マスタ</a>
                <a href="{{ route('masters.users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">ユーザマスタ</a>
                <a href="{{ route('recruitment.status') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500" target="_blank" rel="noopener">公開ページを確認</a>
            </div>
        </header>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full table-auto divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2 text-left whitespace-nowrap">職種</th>
                        <th class="px-4 py-2 text-right whitespace-nowrap">募集人数</th>
                        <th class="px-4 py-2 text-right whitespace-nowrap">決定人数</th>
                        <th class="px-4 py-2 text-right whitespace-nowrap">残り枠</th>
                        <th class="px-4 py-2 text-left">コメント</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">最終更新</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($categories as $category)
                        @php
                            $planned = (int) optional($category->recruitmentInfo)->planned_hires;
                            $decided = (int) ($employedCounts[$category->id] ?? 0);
                            $difference = $planned - $decided;

                            if ($difference < 0) {
                                $rowClass = 'bg-rose-100/80';
                            } elseif ($difference === 0) {
                                $rowClass = 'bg-slate-200/80';
                            } elseif ($difference === 1) {
                                $rowClass = 'bg-amber-100/80';
                            } else {
                                $rowClass = 'bg-white';
                            }
                        @endphp
                        <tr class="transition hover:bg-slate-50 {{ $rowClass }}">
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <span class="font-semibold text-slate-900">{{ $category->name }}</span>
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-right text-slate-700">{{ number_format($planned) }}</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-right text-slate-700">{{ number_format($decided) }}</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-right text-slate-700">{{ number_format(max($difference, 0)) }}</td>
                            <td class="px-4 py-2 align-middle text-slate-700">
                                @if (filled(optional($category->recruitmentInfo)->comment))
                                    <span class="block whitespace-pre-line break-words">{{ optional($category->recruitmentInfo)->comment }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-slate-700">
                                @php $updatedAt = optional($category->recruitmentInfo)->updated_at; @endphp
                                @if ($updatedAt)
                                    {{ $updatedAt->format('Y/m/d H:i') }}
                                @else
                                    <span class="text-slate-400">未更新</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <a href="{{ route('masters.recruitment-info.edit', ['jobCategory' => $category]) }}" class="inline-flex items-center gap-1 rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-600 transition hover:bg-blue-50">
                                    編集
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">表示できる希望職種がありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="mt-6 flex flex-col gap-3 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <span>
                全 {{ number_format($categories->total()) }} 件中
                {{ $categories->firstItem() }}〜{{ $categories->lastItem() }} 件を表示
            </span>
            @if ($categories->hasPages())
                <div class="md:ml-auto">
                    {{ $categories->links() }}
                </div>
            @endif
        </footer>
    </section>
@endsection
