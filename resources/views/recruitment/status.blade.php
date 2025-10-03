@extends('layouts.app')

@section('pageTitle')
    <span class="block w-full max-w-3xl px-4 sm:px-0 mx-auto">募集状況</span>
@endsection

@section('pageDescription')
    <span class="block w-full max-w-3xl px-4 sm:px-0 mx-auto">現在募集中の職種と決定状況を外部公開しています。最新の枠数とコメントをご確認ください。</span>
@endsection

@section('content')
    <div class="mx-auto w-full max-w-3xl px-4 sm:px-0">
        <section class="rounded-3xl bg-white p-5 shadow-md sm:p-6">
            <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">職種別 募集枠一覧</h2>
                    <p class="text-sm text-slate-500">募集人数と現状の就業決定人数を表示しています。残り枠が1名の職種は淡いオレンジ、充足済みはグレーで表示します。</p>
                </div>
                <div class="flex flex-col items-start gap-2 text-xs text-slate-400 sm:items-end sm:text-right">
                    <button type="button" id="recruitment-refresh-button" class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">最新情報に更新</button>
                    <span class="whitespace-nowrap">{{ now()->format('Y/m/d H:i') }} 現在</span>
                    <span id="recruitment-refresh-warning" class="hidden text-amber-500">10秒以内の再読み込みはできません。</span>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-[13px] sm:text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">職種</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">募集人数</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">決定人数</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">残り枠</th>
                            <th class="px-4 py-3 text-left">コメント</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        @php
                            $difference = $category['difference'];
                            $planned = (int) $category['planned'];
                            $remaining = max($difference, 0);

                            if ($difference < 0) {
                                $rowClass = 'bg-rose-100/80';
                            } elseif ($difference === 0) {
                                $rowClass = 'bg-slate-200/80';
                            } elseif ($difference === 1 && $planned >= 2) {
                                $rowClass = 'bg-amber-100/80';
                            } else {
                                $rowClass = 'bg-white';
                            }
                        @endphp
                        <tr class="transition hover:bg-blue-50/60 {{ $rowClass }}">
                            @php
                                $nameParts = explode('(', $category['name'], 2);
                            @endphp
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                @if (count($nameParts) === 2)
                                    <span class="block">{{ $nameParts[0] }}</span>
                                    <span class="block">({{ $nameParts[1] }}</span>
                                @else
                                    {{ $category['name'] }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($category['planned']) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($category['decided']) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($remaining) }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                @if (filled($category['comment']))
                                    <span class="whitespace-pre-line">{{ $category['comment'] }}</span>
                                @else
                                    <span class="text-slate-400">----</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">表示できる募集情報がありません。</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="mt-6 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
                <p>※ 掲載内容は社内システムに登録された最新情報を表示しています。お問い合わせのタイミングによっては充足済みの場合があります。</p>
            </footer>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const refreshButton = document.getElementById('recruitment-refresh-button');
            if (!refreshButton) {
                return;
            }

            const warning = document.getElementById('recruitment-refresh-warning');
            const STORAGE_KEY = 'recruitment-status:last-refresh';
            const COOLDOWN = 10_000; // 10 seconds
            let hideTimer = null;

            const showWarning = (remainingMs) => {
                if (!warning) {
                    return;
                }

                const seconds = Math.ceil(remainingMs / 1000);
                warning.textContent = `10秒以内の再読み込みはできません（あと${seconds}秒）`;
                warning.classList.remove('hidden');

                if (hideTimer) {
                    clearTimeout(hideTimer);
                }
                hideTimer = window.setTimeout(() => {
                    warning.classList.add('hidden');
                }, 3000);
            };

            refreshButton.addEventListener('click', () => {
                const now = Date.now();
                const last = Number(sessionStorage.getItem(STORAGE_KEY));

                if (!Number.isNaN(last) && last > 0) {
                    const elapsed = now - last;
                    if (elapsed < COOLDOWN) {
                        showWarning(COOLDOWN - elapsed);
                        return;
                    }
                }

                sessionStorage.setItem(STORAGE_KEY, String(now));
                if (warning) {
                    warning.classList.add('hidden');
                }
                window.location.reload();
            });
        });
    </script>
@endpush
