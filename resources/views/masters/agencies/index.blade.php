@extends('layouts.app')

@section('pageTitle', '派遣会社マスタ')
@section('pageDescription', '派遣会社の連絡先や担当者情報を管理します。利用停止すると候補者登録画面で選択できなくなります。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">派遣会社一覧</h2>
                <p class="text-sm text-slate-500">有効な会社が上位に表示されます。連絡先や備考を定期的に更新してください。</p>
            </div>
            <a href="{{ route('masters.agencies.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">新規追加</a>
        </header>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 md:flex-row md:items-center md:justify-between">
            <div class="text-sm text-slate-600">
                CSVで一括登録・更新ができます。ヘッダ行は <code>name,contact_person,email,phone,note,is_active</code>（任意で先頭に <code>id</code> を追加可）で指定してください。
            </div>
            <div class="flex flex-col gap-3 md:flex-row md:flex-nowrap md:items-center">
                <a
                    href="{{ route('masters.agencies.export') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 whitespace-nowrap"
                >CSVダウンロード</a>
                <form
                    action="{{ route('masters.agencies.import') }}"
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
                        class="block w-full min-w-[17rem] rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
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
                        <th class="px-4 py-2 text-left whitespace-nowrap">担当者</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">連絡先</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">備考</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">状態</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">最終更新</th>
                        <th class="px-4 py-2 text-left whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($agencies as $agency)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <span class="font-semibold text-slate-900 {{ $agency->is_active ? '' : 'text-slate-400' }}">{{ $agency->name }}</span>
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-slate-700">{{ $agency->contact_person ?? '—' }}</td>
                            <td class="px-4 py-2 align-middle">
                                <div class="flex flex-col gap-1 text-xs text-slate-600">
                                    @if ($agency->email)
                                        <span>📧 {{ $agency->email }}</span>
                                    @endif
                                    @if ($agency->phone)
                                        <span>📞 {{ $agency->phone }}</span>
                                    @endif
                                    @if (!$agency->email && !$agency->phone)
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-2 align-middle text-slate-700">
                                <span class="block max-w-xs text-xs text-slate-600">{{ $agency->note ? \Illuminate\Support\Str::limit($agency->note, 80) : '—' }}</span>
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                @if ($agency->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">利用中</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">停止中</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-slate-700">{{ optional($agency->updated_at)->format('Y/m/d H:i') }}</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap">
                                <a href="{{ route('masters.agencies.edit', $agency) }}" class="inline-flex items-center gap-1 rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-600 transition hover:bg-blue-50">
                                    編集
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">登録済みの派遣会社がありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="mt-6 flex flex-col gap-3 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <span>
                全 {{ number_format($agencies->total()) }} 件中
                {{ $agencies->firstItem() }}〜{{ $agencies->lastItem() }} 件を表示
            </span>
            @if ($agencies->hasPages())
                <div class="md:ml-auto">
                    {{ $agencies->links() }}
                </div>
            @endif
        </footer>
    </section>
@endsection
