@extends('layouts.app')

@section('pageTitle', '募集情報の編集')
@section('pageDescription', '外部公開ページに表示される募集枠とコメントを更新します。')

@section('content')
    <section class="space-y-8">
        <div class="rounded-3xl bg-white p-6 shadow-md">
            <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ $category->name }}</h2>
                    <p class="text-sm text-slate-500">ID: {{ $category->id }} ／ 表示順: {{ $category->sort_order }}</p>
                </div>
                <a href="{{ route('masters.recruitment-info.index') }}" class="text-sm font-semibold text-blue-600 transition hover:text-blue-500">一覧へ戻る</a>
            </header>

            <section class="mt-6 grid gap-4 md:grid-cols-3">
                @php
                    $planned = (int) old('planned_hires', optional($category->recruitmentInfo)->planned_hires ?? 0);
                    $diffValue = $planned - $decidedCount;
                    if ($diffValue < 0) {
                        $diffColor = 'text-rose-600';
                    } elseif ($diffValue === 0) {
                        $diffColor = 'text-slate-600';
                    } elseif ($diffValue === 1) {
                        $diffColor = 'text-amber-600';
                    } else {
                        $diffColor = 'text-emerald-600';
                    }
                @endphp
                <article class="rounded-2xl bg-slate-50 p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">募集人数</h3>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($planned) }} 名</p>
                </article>
                <article class="rounded-2xl bg-slate-50 p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">決定人数</h3>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($decidedCount) }} 名</p>
                </article>
                <article class="rounded-2xl bg-slate-50 p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">残り枠</h3>
                    <p class="mt-2 text-2xl font-semibold {{ $diffColor }}">{{ number_format(max($diffValue, 0)) }} 名</p>
                </article>
            </section>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-md">
            <form method="POST" action="{{ route('masters.recruitment-info.update', ['jobCategory' => $category]) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="planned_hires" class="block text-sm font-semibold text-slate-700">募集人数</label>
                        <input type="number" id="planned_hires" name="planned_hires" value="{{ old('planned_hires', optional($category->recruitmentInfo)->planned_hires ?? 0) }}" min="0" max="65535" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <p class="mt-1 text-xs text-slate-500">未定の場合は 0 のままにしてください。</p>
                        @error('planned_hires')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="recruitment_comment" class="block text-sm font-semibold text-slate-700">募集コメント</label>
                        <textarea id="recruitment_comment" name="recruitment_comment" rows="4" maxlength="1000" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="例）未経験歓迎、週3日〜相談可など">{{ old('recruitment_comment', optional($category->recruitmentInfo)->comment) }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">外部公開ページにそのまま表示されます。機微情報は記載しないでください。</p>
                        @error('recruitment_comment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('masters.recruitment-info.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル</a>
                    <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">更新する</button>
                </div>
            </form>
        </div>
    </section>
@endsection
