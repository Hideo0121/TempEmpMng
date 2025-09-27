@extends('layouts.app')

@section('pageTitle', 'ユーザの新規登録')
@section('pageDescription', 'システムにログインできるユーザを追加します。登録後すぐに通知メール等は送信されません。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">基本情報</h2>
            <a href="{{ route('masters.users.index') }}" class="text-sm font-semibold text-blue-600 transition hover:text-blue-500">一覧へ戻る</a>
        </header>

        <form method="POST" action="{{ route('masters.users.store') }}" class="space-y-6">
            @csrf

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700">氏名 <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="120" required
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">メールアドレス <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="191" required
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">権限 <span class="text-red-500">*</span></label>
                    <div class="mt-2 flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm">
                            <input type="radio" name="role" value="manager" class="h-4 w-4 text-blue-600 focus:ring-blue-500" {{ old('role', 'staff') === 'manager' ? 'checked' : '' }}>
                            管理者（マスタ管理可）
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm">
                            <input type="radio" name="role" value="staff" class="h-4 w-4 text-blue-600 focus:ring-blue-500" {{ old('role', 'staff') === 'staff' ? 'checked' : '' }}>
                            一般（閲覧・登録のみ）
                        </label>
                    </div>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" {{ old('is_active', true) ? 'checked' : '' }}>
                        利用中にする
                    </label>
                    <p class="mt-2 text-xs text-slate-500">利用停止にするとログインできなくなります。</p>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">初期パスワード <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" minlength="8" maxlength="191" required
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <p class="mt-1 text-xs text-slate-500">登録後にユーザ本人へお知らせください。</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">パスワード（確認） <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" maxlength="191" required
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('masters.users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">キャンセル</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">登録する</button>
            </div>
        </form>
    </section>
@endsection
