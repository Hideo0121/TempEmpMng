@extends('layouts.app')

@section('pageTitle', '派遣会社の編集')
@section('pageDescription', '派遣会社の連絡先情報や利用状態を更新します。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $agency->name }}</h2>
                <p class="text-sm text-slate-500">ID: {{ $agency->id }}</p>
            </div>
            <a href="{{ route('masters.agencies.index') }}" class="text-sm font-semibold text-blue-600 transition hover:text-blue-500">一覧へ戻る</a>
        </header>

        <form method="POST" action="{{ route('masters.agencies.update', $agency) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">名称 <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $agency->name) }}" maxlength="120" required
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="contact_person" class="block text-sm font-semibold text-slate-700">担当者</label>
                    <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $agency->contact_person) }}" maxlength="80"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('contact_person')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-700">電話番号</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $agency->phone) }}" maxlength="40"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">メールアドレス</label>
                <input type="email" id="email" name="email" value="{{ old('email', $agency->email) }}" maxlength="191"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="note" class="block text-sm font-semibold text-slate-700">備考</label>
                <textarea id="note" name="note" rows="4" maxlength="2000"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('note', $agency->note) }}</textarea>
                @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $agency->is_active)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    利用中にする
                </label>
                @error('is_active')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('masters.agencies.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">更新する</button>
            </div>
        </form>
    </section>
@endsection
