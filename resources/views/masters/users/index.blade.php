@extends('layouts.app')

@section('pageTitle', 'ユーザマスタ')
@section('pageDescription', 'システムにログインできるユーザ情報と権限・利用状態を管理します。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">ユーザ一覧</h2>
                <p class="text-sm text-slate-500">有効ユーザを優先表示しています。権限は manager（管理者）と staff（一般）の2種類です。</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('masters.job-categories.index') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">希望職種一覧</a>
                <a href="{{ route('masters.candidate-statuses.index') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">ステータス一覧</a>
                <a href="{{ route('masters.agencies.index') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">派遣会社一覧</a>
                <a href="{{ route('masters.users.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">新規追加</a>
            </div>
        </header>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 md:flex-row md:items-center md:justify-between">
            <div class="text-sm text-slate-600">
                CSVで一括登録・更新ができます。ヘッダ行は <code>name,email,role,is_active,password</code>（任意で先頭に <code>id</code> を追加可）で指定してください。<br>
                新規登録時は <code>password</code> 列に8文字以上の仮パスワードを記載してください。空欄の場合はランダムパスワードが設定されます。
            </div>
            <div class="flex flex-col gap-3 md:flex-row md:flex-nowrap md:items-center">
                <a
                    href="{{ route('masters.users.export') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 whitespace-nowrap"
                >CSVダウンロード</a>
                <form
                    action="{{ route('masters.users.import') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="flex flex-wrap items-center gap-3 md:flex-nowrap"
                >
                    @csrf
                    <input
                        type="file"
                        name="file"
                        accept=".csv,text/csv"
                        required
                        class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 md:w-64 lg:w-72"
                    >
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500 whitespace-nowrap"
                    >取込</button>
                </form>
            </div>
        </div>

        @error('file')
            <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $message }}
            </div>
        @enderror

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full table-auto divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2 text-left whitespace-nowrap">氏名</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">メールアドレス</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">権限</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">状態</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">最終更新</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($users as $user)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <span class="font-semibold {{ $user->is_active ? 'text-slate-900' : 'text-slate-400' }}">{{ $user->name }}</span>
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-slate-700">{{ $user->email }}</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                @if ($user->role === 'manager')
                                    <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">管理者</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">一般</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">利用中</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">停止中</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-slate-700">{{ optional($user->updated_at)->format('Y/m/d H:i') }}</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <a href="{{ route('masters.users.edit', $user) }}" class="inline-flex items-center gap-1 rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-600 transition hover:bg-blue-50">
                                    編集
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">登録済みのユーザがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="mt-6 flex flex-col gap-3 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <span>
                全 {{ number_format($users->total()) }} 件中
                {{ $users->firstItem() }}〜{{ $users->lastItem() }} 件を表示
            </span>
            @if ($users->hasPages())
                <div class="md:ml-auto">
                    {{ $users->links() }}
                </div>
            @endif
        </footer>
    </section>
@endsection
