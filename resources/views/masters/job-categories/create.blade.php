@extends('layouts.app')

@section('pageTitle', '希望職種の新規登録')
@section('pageDescription', '紹介者登録フォームに表示する希望職種を追加します。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">基本情報</h2>
            <a href="{{ route('masters.job-categories.index') }}" class="text-sm font-semibold text-blue-600 transition hover:text-blue-500">一覧へ戻る</a>
        </header>

        <form method="POST" action="{{ route('masters.job-categories.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">名称 <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" required maxlength="100">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="sort_order" class="block text-sm font-semibold text-slate-700">表示順</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="65535"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        利用中にする
                    </label>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('masters.job-categories.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">登録する</button>
            </div>
        </form>
    </section>
@endsection
