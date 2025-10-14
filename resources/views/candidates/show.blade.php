@extends('layouts.app')

@section('pageTitle', '紹介者詳細')
@section('pageDescription', '紹介者の詳細情報とスキルシートを確認できます。')

@section('content')
    <section class="space-y-6">
        <article class="rounded-3xl bg-white p-6 shadow-md">
            @php
                $jobPreferences = collect([
                    ['label' => '第1希望', 'value' => optional($candidate->wishJob1)->name],
                    ['label' => '第2希望', 'value' => optional($candidate->wishJob2)->name],
                    ['label' => '第3希望', 'value' => optional($candidate->wishJob3)->name],
                ]);

                $visitSlots = collect([
                    ['label' => '見学候補 1', 'datetime' => $candidate->visit_candidate1_at],
                    ['label' => '見学候補 2', 'datetime' => $candidate->visit_candidate2_at],
                    ['label' => '見学候補 3', 'datetime' => $candidate->visit_candidate3_at],
                ])->filter(fn ($slot) => filled($slot['datetime']));

                $handlers = collect([
                    ['label' => '職場見学対応者 1', 'value' => optional($candidate->handler1)->name],
                    ['label' => '職場見学対応者 2', 'value' => optional($candidate->handler2)->name],
                ]);

                $confirmedInterview = $candidate->confirmedInterview;
                $confirmedAt = optional($confirmedInterview)->scheduled_at;
                $remind30mStatus = $confirmedInterview
                    ? ($confirmedInterview->remind_30m_enabled ? 'ON' : 'OFF')
                    : '未登録';
                $employmentStartAt = $candidate->employment_start_at;
                $assignmentCodes = collect([
                    ['label' => 'コード A', 'value' => $candidate->assignment_worker_code_a],
                    ['label' => 'コード B', 'value' => $candidate->assignment_worker_code_b],
                ])->filter(fn ($item) => filled($item['value']));
            @endphp

            <header class="flex flex-col gap-4 border-b border-slate-200 pb-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 flex flex-wrap items-center gap-2">
                        <span>{{ $candidate->name }}</span>
                        <span class="text-sm text-slate-500">({{ $candidate->name_kana }})</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">ID: {{ str_pad((string) $candidate->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">紹介日: {{ optional($candidate->introduced_on)->format('Y/m/d') ?? '未設定' }}</p>
                    <p class="text-xs text-slate-400">状態変化日: {{ optional($candidate->status_changed_on)->format('Y/m/d') ?? '—' }}</p>
                </div>
                <div class="flex flex-col items-start justify-center gap-3 md:items-end md:gap-2">
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-4 py-1 text-sm font-semibold text-blue-700">
                        {{ optional($candidate->status)->label ?? 'ステータス未設定' }}
                    </span>
                    <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                        ← 一覧に戻る
                    </a>
                </div>
            </header>

            <div class="mt-6 grid gap-6 lg:grid-cols-12">
                <section class="lg:col-span-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">派遣会社</h3>
                        <p class="mt-1 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ optional($candidate->agency)->name ?? '未設定' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">希望職種</h3>
                        <div class="mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            @if ($jobPreferences->isEmpty())
                                <p class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">希望職種は未設定です。</p>
                            @else
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($jobPreferences as $preference)
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                            <p class="text-xs font-semibold text-slate-500">{{ $preference['label'] }}</p>
                                            <p class="mt-1 font-medium text-slate-800">{{ $preference['value'] ?? '未設定' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                                <p class="text-xs font-semibold text-blue-600">就業する職種</p>
                                @if ($candidate->decidedJob && \App\Models\CandidateStatus::isEmployed((string) $candidate->status_code))
                                    <p class="mt-1 text-sm font-semibold text-blue-900">{{ $candidate->decidedJob->name }}</p>
                                @else
                                    <p class="mt-1 text-sm text-slate-500">就業決定時に設定されます。</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">紹介文</h3>
                        <div class="mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                            {!! nl2br(e($candidate->introduction_note ?? '未入力')) !!}
                        </div>
                    </div>
                </section>

                <section class="lg:col-span-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">職場見学対応者</h3>
                        <div class="mt-2 space-y-2">
                            @foreach ($handlers as $handler)
                                <div class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-700">
                                    <span class="font-semibold text-slate-600">{{ $handler['label'] }}</span>
                                    <span>{{ $handler['value'] ?? '未設定' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">見学確定日時</h3>
                        <div class="mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                            @if ($confirmedAt)
                                <p class="font-semibold text-slate-800">{{ optional($confirmedAt)->format('Y/m/d H:i') }}</p>
                            @else
                                <p class="text-slate-500">確定した見学日時はまだ登録されていません。</p>
                            @endif

                            <dl class="mt-3 text-xs text-slate-600">
                                <dt class="font-semibold">30分前リマインドを送信する</dt>
                                <dd class="mt-1 text-slate-800">{{ $remind30mStatus }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">見学候補日程</h3>
                        <div class="mt-2 space-y-2">
                            @if ($visitSlots->isEmpty())
                                <p class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">見学候補は登録されていません。</p>
                            @else
                                @foreach ($visitSlots as $slot)
                                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-700">
                                        <span class="font-semibold text-slate-600">{{ $slot['label'] }}</span>
                                        <span>{{ optional($slot['datetime'])->format('Y/m/d H:i') }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">就業開始予定</h3>
                        <div class="mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                            <p class="font-semibold text-slate-800">{{ optional($employmentStartAt)->format('Y/m/d H:i') ?? '未設定' }}</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @forelse ($assignmentCodes as $code)
                                    <div class="flex flex-col rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                        <span class="font-semibold text-slate-500">{{ $code['label'] }}</span>
                                        <span class="mt-1 text-slate-800">{{ $code['value'] }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 sm:col-span-2">アサインコードは登録されていません。</p>
                                @endforelse
                            </div>
                            <dl class="mt-3 text-xs text-slate-600">
                                <dt class="font-semibold">配属ロッカー</dt>
                                <dd class="mt-1 text-slate-800">{{ $candidate->assignment_locker ?? '未設定' }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-semibold text-slate-500">交通費（日額）</p>
                            <p class="mt-2 text-sm text-slate-800">{{ $candidate->transport_cost_day ? number_format($candidate->transport_cost_day) . ' 円' : '未設定' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-semibold text-slate-500">交通費（月額）</p>
                            <p class="mt-2 text-sm text-slate-800">{{ $candidate->transport_cost_month ? number_format($candidate->transport_cost_month) . ' 円' : '未設定' }}</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600">その他条件・メモ</h3>
                        <form method="post" action="{{ route('candidates.memo.update', $candidate) }}" class="mt-2 space-y-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="back" value="{{ $backUrl }}">
                            <textarea name="other_conditions" rows="6" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="条件やメモを入力してください（2000文字まで）">{{ old('other_conditions', $candidate->other_conditions) }}</textarea>
                            @error('other_conditions')
                                <p class="text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                            <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                                <span>※ 「保存する」ボタンで入力内容は保存され、全スタッフが参照できます。</span>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">
                                    保存する
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </article>

        <article class="rounded-3xl bg-white p-6 shadow-md">
            <header class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">スキルシート</h3>
                <span class="text-xs font-semibold text-slate-400">最大 5 件まで保存</span>
            </header>

            @php
                $formatSize = static function ($bytes) {
                    if (empty($bytes)) {
                        return '0KB';
                    }

                    if ($bytes >= 1048576) {
                        return number_format($bytes / 1048576, 1) . 'MB';
                    }

                    return number_format($bytes / 1024, 0) . 'KB';
                };

                $formatDate = static function ($timestamp) {
                    if (!$timestamp) {
                        return '';
                    }

                    return optional($timestamp)->format('Y/m/d H:i');
                };
            @endphp

            <div class="mt-4 space-y-3">
                @forelse ($candidate->skillSheets as $index => $sheet)
                    @php
                        $previewUrl = route('candidates.skill-sheets.preview', [$candidate, $sheet]);
                    @endphp
                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between"
                        data-viewer-item
                        data-viewer-url="{{ $previewUrl }}"
                        data-viewer-name="{{ $sheet->original_name }}"
                        data-viewer-active="{{ $index === 0 ? 'true' : 'false' }}">
                        <div class="flex flex-1 items-start gap-3">
                            <span class="mt-1 text-xl">📄</span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800" title="{{ $sheet->original_name }}">{{ $sheet->original_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $formatSize($sheet->size_bytes) }}
                                    @if ($sheet->updated_at)
                                        ・ {{ $formatDate($sheet->updated_at) }}
                                    @endif
                                    @if ($sheet->uploader)
                                        ・ {{ $sheet->uploader->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="inline-flex items-center gap-2 rounded-full border border-blue-200 px-4 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-50"
                                data-viewer-trigger>
                                ビューアで表示
                            </button>
                            <a href="{{ route('candidates.skill-sheets.download', [$candidate, $sheet]) }}"
                                class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-500">
                                ダウンロード
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                        アップロードされたスキルシートはありません。
                    </div>
                @endforelse
            </div>

            @if ($candidate->skillSheets->isNotEmpty())
                <div class="mt-6 space-y-3" data-viewer-container>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700" data-viewer-title>{{ $candidate->skillSheets->first()->original_name }}</p>
                        <span class="text-xs text-slate-400">PDF ビューア</span>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <iframe
                            src="{{ route('candidates.skill-sheets.preview', [$candidate, $candidate->skillSheets->first()]) }}"
                            title="スキルシートプレビュー"
                            class="h-[1400px] w-full"
                            data-viewer-frame
                            allow="fullscreen"
                        ></iframe>
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection

@if ($candidate->skillSheets->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('[data-viewer-container]');
            if (!container) {
                return;
            }

            const frame = container.querySelector('[data-viewer-frame]');
            const title = container.querySelector('[data-viewer-title]');
            const items = document.querySelectorAll('[data-viewer-item]');

            const activateItem = (target) => {
                items.forEach((item) => {
                    if (item === target) {
                        item.setAttribute('data-viewer-active', 'true');
                        item.classList.add('ring', 'ring-blue-200');
                    } else {
                        item.setAttribute('data-viewer-active', 'false');
                        item.classList.remove('ring', 'ring-blue-200');
                    }
                });
            };

            items.forEach((item) => {
                const trigger = item.querySelector('[data-viewer-trigger]');
                if (!trigger) {
                    return;
                }

                trigger.addEventListener('click', () => {
                    const url = item.getAttribute('data-viewer-url');
                    const name = item.getAttribute('data-viewer-name');

                    if (frame && url) {
                        frame.src = url;
                    }

                    if (title && name) {
                        title.textContent = name;
                    }

                    activateItem(item);
                });

                if (item.getAttribute('data-viewer-active') === 'true') {
                    activateItem(item);
                }
            });
        });
    </script>
@endif
