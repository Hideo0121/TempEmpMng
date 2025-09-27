@extends('layouts.app')

@section('pageTitle', 'マスタ管理')
@section('pageDescription', '希望職種・ステータス・派遣会社・ユーザのマスタを最新状態に保ちます。')

@section('content')
    <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <article class="flex flex-col justify-between rounded-3xl bg-white p-6 shadow-md transition hover:-translate-y-1 hover:shadow-lg">
            <div>
                <p class="text-sm font-semibold text-blue-600">Job Categories</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">希望職種マスタ</h2>
                <p class="mt-2 text-sm text-slate-600">求人票で利用する職種名称と表示順を管理します。</p>
            </div>
            <div class="mt-6 flex items-center justify-between text-sm">
                <span class="text-slate-500">登録番号・表示順を一元管理</span>
                <a href="{{ route('masters.job-categories.index') }}" class="rounded-full bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-500">一覧へ</a>
            </div>
        </article>

        <article class="flex flex-col justify-between rounded-3xl bg-white p-6 shadow-md transition hover:-translate-y-1 hover:shadow-lg">
            <div>
                <p class="text-sm font-semibold text-amber-600">Candidate Statuses</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">ステータスマスタ</h2>
                <p class="mt-2 text-sm text-slate-600">候補者の進捗ステータス名称・表示色・並び順を編集します。</p>
            </div>
            <div class="mt-6 flex items-center justify-between text-sm">
                <span class="text-slate-500">一覧バッジの配色と名称を設定</span>
                <a href="{{ route('masters.candidate-statuses.index') }}" class="rounded-full bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-500">一覧へ</a>
            </div>
        </article>

        <article class="flex flex-col justify-between rounded-3xl bg-white p-6 shadow-md transition hover:-translate-y-1 hover:shadow-lg">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Agencies</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">派遣会社マスタ</h2>
                <p class="mt-2 text-sm text-slate-600">連絡先や担当者を最新化して紹介対応をスムーズにします。</p>
            </div>
            <div class="mt-6 flex items-center justify-between text-sm">
                <span class="text-slate-500">問い合わせ先を整理</span>
                <a href="{{ route('masters.agencies.index') }}" class="rounded-full bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-500">一覧へ</a>
            </div>
        </article>

        <article class="flex flex-col justify-between rounded-3xl bg-white p-6 shadow-md transition hover:-translate-y-1 hover:shadow-lg">
            <div>
                <p class="text-sm font-semibold text-purple-600">Users</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">ユーザマスタ</h2>
                <p class="mt-2 text-sm text-slate-600">ログインアカウントの権限と利用状態を管理し、CSVで一括更新できます。</p>
            </div>
            <div class="mt-6 flex items-center justify-between text-sm">
                <span class="text-slate-500">権限と利用状態を統制</span>
                <a href="{{ route('masters.users.index') }}" class="rounded-full bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-500">一覧へ</a>
            </div>
        </article>
    </section>
@endsection
