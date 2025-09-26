@extends('layouts.app')

@section('pageTitle', '希望職種の編集')
@section('pageDescription', '選択した希望職種の名称や表示順、利用状態を変更できます。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $category->name }}</h2>
                <p class="text-sm text-slate-500">ID: {{ $category->id }}</p>
            </div>
            <a href="{{ route('masters.job-categories.index') }}" class="text-sm font-semibold text-blue-600 transition hover:text-blue-500">一覧へ戻る</a>
        </header>

        <form method="POST" action="{{ route('masters.job-categories.update', $category) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">名称 <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" required maxlength="100">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="sort_order" class="block text-sm font-semibold text-slate-700">表示順</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0" max="65535"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        利用中にする
                    </label>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('masters.job-categories.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">更新する</button>
            </div>
        </form>
    </section>
@endsection
