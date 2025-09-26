@extends('layouts.app')

@section('pageTitle', '希望職種マスタ')
@section('pageDescription', '紹介者登録で使用する希望職種の名称と表示順を管理します。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">希望職種一覧</h2>
                <p class="text-sm text-slate-500">表示順の昇順で表示しています。無効化した項目はグレー表示です。</p>
            </div>
            <a href="{{ route('masters.job-categories.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">新規追加</a>
        </header>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 md:flex-row md:items-center md:justify-between">
            <div class="text-sm text-slate-600">
                CSVで一括登録・更新ができます。ヘッダ行は <code>name,sort_order,is_active</code>（任意で先頭に <code>id</code> を追加可）で指定してください。
            </div>
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <a
                    href="{{ route('masters.job-categories.export') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 whitespace-nowrap"
                >CSVダウンロード</a>
                <form
                    action="{{ route('masters.job-categories.import') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="flex items-center gap-3"
                >
                    @csrf
                    <input
                        type="file"
                        name="file"
                        accept=".csv,text/csv"
                        required
                        class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500"
                    >取り込み</button>
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
                        <th class="px-4 py-2 text-left whitespace-nowrap">名称</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">表示順</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">状態</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">最終更新</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($categories as $category)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <span class="font-semibold text-slate-900 {{ $category->is_active ? '' : 'text-slate-400' }}">{{ $category->name }}</span>
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-slate-700">{{ $category->sort_order }}</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                @if ($category->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">利用中</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">停止中</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-slate-700">{{ optional($category->updated_at)->format('Y/m/d H:i') }}</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <a href="{{ route('masters.job-categories.edit', $category) }}" class="inline-flex items-center gap-1 rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-600 transition hover:bg-blue-50">
                                    編集
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">登録済みの希望職種がありません。</td>
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
