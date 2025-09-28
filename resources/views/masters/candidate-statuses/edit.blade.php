@extends('layouts.app')

@section('pageTitle', 'ステータスの編集')
@section('pageDescription', '候補者ステータスの名称や表示色、利用状態を更新します。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $status->label }}</h2>
                <p class="text-sm font-mono text-slate-500">コード: {{ $status->code }}</p>
            </div>
            <a href="{{ route('masters.candidate-statuses.index') }}" class="text-sm font-semibold text-blue-600 transition hover:text-blue-500">一覧へ戻る</a>
        </header>

        <form method="POST" action="{{ route('masters.candidate-statuses.update', $status) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="code" class="block text-sm font-semibold text-slate-700">コード</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $status->code) }}" readonly
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-mono text-slate-600">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="label" class="block text-sm font-semibold text-slate-700">表示名 <span class="text-red-500">*</span></label>
                    <input type="text" id="label" name="label" value="{{ old('label', $status->label) }}" maxlength="50"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" required>
                    @error('label')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <label for="color_code" class="block text-sm font-semibold text-slate-700">カラーコード <span class="text-red-500">*</span></label>
                    <div class="mt-1 flex items-center gap-3">
                        <input type="color" id="color_picker" value="{{ old('color_code', $status->color_code) }}"
                            class="h-10 w-12 rounded-xl border border-slate-300" oninput="document.getElementById('color_code').value = this.value.toUpperCase();">
                        <input type="text" id="color_code" name="color_code" value="{{ old('color_code', $status->color_code) }}" maxlength="7"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" required
                            oninput="if (this.value.length === 7) document.getElementById('color_picker').value = this.value;">
                    </div>
                    @error('color_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-semibold text-slate-700">表示順</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $status->sort_order) }}" min="0" max="65535"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $status->is_active)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        利用中にする
                    </label>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                    <div>
                        <input type="hidden" name="is_employed_state" value="0">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="is_employed_state" value="1" @checked(old('is_employed_state', $status->is_employed_state)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            就業判定対象にする
                        </label>
                        <p class="mt-1 text-xs text-slate-500">「就業する職種」の必須制御など、就業決定ステータスとして扱います。</p>
                        @error('is_employed_state')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('masters.candidate-statuses.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">更新する</button>
            </div>
        </form>
    </section>
@endsection
