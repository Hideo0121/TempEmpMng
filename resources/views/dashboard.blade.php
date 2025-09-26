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
                    <p class="text-sm text-slate-700">本日予定の見学: <span class="font-semibold text-blue-600">3件</span></p>
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
@endsection
