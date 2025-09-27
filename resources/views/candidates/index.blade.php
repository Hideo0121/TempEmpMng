@extends('layouts.app')

@section('pageTitle', '紹介者一覧')
@section('pageDescription', '未閲覧バッジ・見学スケジュール・ステータスをまとめて確認できます。検索条件は画面上部から絞り込み可能です。')

@section('content')
    <section class="rounded-3xl bg-white p-6 shadow-md">
        <form class="grid gap-4 lg:grid-cols-12" action="{{ route('candidates.index') }}" method="get">
            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-slate-700" for="keyword">自由語検索</label>
                <input id="keyword" name="keyword" type="text" value="{{ $filters['keyword'] }}"
                    placeholder="氏名・条件・メモなど"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700" for="agency">派遣会社</label>
                <select id="agency" name="agency"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">すべて</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected((string) $filters['agency'] === (string) $agency->id)>{{ $agency->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700" for="wish_job">希望職種</label>
                <select id="wish_job" name="wish_job"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">すべて</option>
                    @foreach ($jobCategories as $jobCategory)
                        <option value="{{ $jobCategory->id }}" @selected((string) $filters['wish_job'] === (string) $jobCategory->id)>{{ $jobCategory->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700" for="decided_job">就業する職種</label>
                <select id="decided_job" name="decided_job"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">すべて</option>
                    @foreach ($jobCategories as $jobCategory)
                        <option value="{{ $jobCategory->id }}" @selected((string) $filters['decided_job'] === (string) $jobCategory->id)>{{ $jobCategory->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-slate-700">ステータス</label>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    @foreach ($statuses as $status)
                        <label class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1">
                            <input type="checkbox" name="status[]" value="{{ $status->code }}"
                                @checked(in_array($status->code, $filters['status'], true))
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>{{ $status->label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">紹介日</label>
                <div class="mt-1 grid grid-cols-2 gap-2">
                    <input type="date" name="introduced_from" value="{{ $filters['introduced_from'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <input type="date" name="introduced_to" value="{{ $filters['introduced_to'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">見学確定日時</label>
                <div class="mt-1 grid grid-cols-2 gap-2">
                    <input type="datetime-local" name="interview_from" value="{{ $filters['interview_from'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <input type="datetime-local" name="interview_to" value="{{ $filters['interview_to'] }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">対応者</label>
                <select name="handler" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">すべて</option>
                    @foreach ($handlers as $handler)
                        <option value="{{ $handler->id }}" @selected((string) $filters['handler'] === (string) $handler->id)>{{ $handler->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2 lg:pr-4 xl:pr-6">
                <label class="block text-sm font-semibold text-slate-700">30分前リマインド</label>
                    <div class="mt-2 flex items-center gap-3 text-sm">
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="all" @checked($filters['remind_30m'] === 'all') class="text-blue-600">すべて</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="on" @checked($filters['remind_30m'] === 'on') class="text-blue-600">ONのみ</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="remind_30m" value="off" @checked($filters['remind_30m'] === 'off') class="text-blue-600">OFFのみ</label>
                </div>
            </div>

            <div class="lg:col-span-2 lg:pl-6 xl:pl-10">
                <label class="block text-sm font-semibold text-slate-700">閲覧状態</label>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm md:flex-nowrap">
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="all" @checked($filters['view_state'] === 'all') class="text-blue-600">すべて</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="unread" @checked($filters['view_state'] === 'unread') class="text-blue-600">未閲覧</label>
                        <label class="flex items-center gap-2 whitespace-nowrap"><input type="radio" name="view_state" value="read" @checked($filters['view_state'] === 'read') class="text-blue-600">閲覧済</label>
                </div>
            </div>

            <div class="lg:col-span-12 flex flex-wrap items-center justify-end gap-3 pt-2">
                <button type="button" data-filter-clear="{{ route('candidates.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">クリア</button>
                <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">検索</button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl bg-white shadow-md">
        <header class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div class="flex items-center gap-3 text-sm text-slate-600">
                <span>未閲覧優先 → 紹介日降順（既定）</span>
                <button class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">並び替えを変更</button>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <button class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 transition hover:bg-slate-200">CSVエクスポート</button>
                <button class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600 transition hover:bg-slate-200">カラム設定</button>
            </div>
        </header>

        <div class="overflow-x-auto">
            <table class="min-w-[1500px] w-full divide-y divide-slate-200">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="p-4 text-left">閲覧</th>
                        <th class="p-4 text-left">氏名</th>
                        <th class="p-4 text-left">派遣会社</th>
                        <th class="p-4 text-left w-64">希望職種</th>
                        <th class="p-4 text-left">就業する職種</th>
                        <th class="p-4 text-left">紹介日</th>
                        <th class="p-4 text-left">見学確定日時</th>
                        <th class="p-4 text-left whitespace-nowrap">ステータス</th>
                        <th class="p-4 text-left">状態変化日</th>
                        <th class="p-4 text-left">アクション</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-sm">
                    @forelse ($candidates as $candidate)
                        @php
                            $viewRecord = $candidate->views->first();
                            $latestInterview = $candidate->interviews->first();
                            $status = $candidate->status;
                            $jobPreferences = collect([
                                ['label' => '第1希望', 'value' => optional($candidate->wishJob1)->name],
                                ['label' => '第2希望', 'value' => optional($candidate->wishJob2)->name],
                                ['label' => '第3希望', 'value' => optional($candidate->wishJob3)->name],
                            ])->filter(fn ($item) => filled($item['value']));
                            $statusColor = $status?->color_code ?: '#DFE7F3';
                            $statusGradientStart = $statusColor . 'cc';
                            $statusGradientEnd = $statusColor . '99';
                            $statusBorderColor = $statusColor . '80';
                            $statusLabel = $status->label ?? 'ステータス未設定';
                        @endphp
                        <tr class="transition hover:bg-slate-50" data-candidate-row="{{ $candidate->id }}">
                            <td class="p-4">
                                @if (!$viewRecord)
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white" title="未閲覧">●</span>
                                @else
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600" title="閲覧済">✓</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="font-semibold text-slate-900">{{ $candidate->name }}</div>
                                <div class="text-xs text-slate-500">ID: {{ str_pad((string) $candidate->id, 6, '0', STR_PAD_LEFT) }}</div>
                            </td>
                            <td class="p-4 text-slate-700">{{ optional($candidate->agency)->name ?? '未設定' }}</td>
                            <td class="p-4 align-top">
                                <div class="flex max-w-[18rem] flex-wrap gap-2 text-xs">
                                    @forelse ($jobPreferences as $index => $job)
                                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ $job['label'] }}: {{ $job['value'] }}</span>
                                    @empty
                                        <span class="text-slate-400">希望職種未設定</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="p-4 align-top text-slate-700" data-role="decided-job">
                                @if (\App\Models\CandidateStatus::isEmployed((string) $candidate->status_code))
                                    <span class="inline-flex min-h-[2.25rem] items-center rounded-xl bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">
                                        {{ optional($candidate->decidedJob)->name ?? '未設定' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-slate-700">{{ optional($candidate->introduced_on)->format('Y/m/d') }}</td>
                            <td class="p-4 text-slate-700">{{ optional(optional($latestInterview)->scheduled_at)->format('Y/m/d H:i') ?? '未調整' }}</td>
                            <td class="p-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset"
                                    data-role="status-label"
                                    style="background-image: linear-gradient(135deg, {{ $statusGradientStart }}, {{ $statusGradientEnd }}); border-color: {{ $statusBorderColor }}; --tw-ring-color: {{ $statusBorderColor }};"
                                >
                                    <span class="inline-block h-2 w-2 rounded-full bg-slate-600/60 shadow" aria-hidden="true"></span>
                                    <span data-role="status-text">{{ $statusLabel }}</span>
                                </span>
                            </td>
                            <td class="p-4 text-slate-700" data-role="status-changed-on">{{ optional($candidate->status_changed_on)->format('Y/m/d') ?? '—' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <a href="{{ route('candidates.show', ['candidate' => $candidate, 'back' => request()->fullUrl()]) }}" class="rounded-full border border-blue-200 px-3 py-1 text-blue-600 transition hover:bg-blue-50">詳細</a>
                                    @if (auth()->user()?->isManager())
                                        <a href="{{ route('candidates.edit', ['candidate' => $candidate, 'back' => request()->fullUrl()]) }}" class="rounded-full border border-blue-200 px-3 py-1 text-blue-600 transition hover:bg-blue-50">編集</a>
                                    @endif
                                    <button type="button" class="rounded-full border border-amber-200 px-3 py-1 text-amber-600 transition hover:bg-amber-50" data-role="status-open" data-candidate-id="{{ $candidate->id }}" data-candidate-name="{{ $candidate->name }}" data-current-status="{{ $candidate->status_code }}" data-current-decided-job="{{ $candidate->decided_job_category_id }}" data-action="{{ route('candidates.status.update', $candidate) }}">ステータス変更</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-6 text-center text-slate-500">該当する紹介者は見つかりませんでした。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="flex flex-col gap-4 border-t border-slate-200 px-6 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <span>
                全 {{ number_format($candidates->total()) }} 件中
                {{ $candidates->firstItem() }}〜{{ $candidates->lastItem() }} 件を表示
            </span>
            @if ($candidates->hasPages())
                <div class="md:ml-auto">
                    {{ $candidates->onEachSide(1)->links() }}
                </div>
            @endif
        </footer>
    </section>
    <div id="status-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div data-modal-overlay class="absolute inset-0 bg-slate-900/60"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">ステータス変更</h2>
                    <p id="status-modal-candidate-name" class="mt-1 text-sm text-slate-500"></p>
                </div>
                <button type="button" class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="閉じる" data-modal-dismiss>&times;</button>
            </div>
            <form id="status-form" class="mt-6 space-y-4" method="post">
                @csrf
                @method('PATCH')
                <div>
                    <label for="status-modal-select" class="block text-sm font-semibold text-slate-700">新しいステータス</label>
                    <select id="status-modal-select" name="status_code" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" required>
                        <option value="" disabled selected>選択してください</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->code }}">{{ $statusOption->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1" data-modal-decided-job data-status-employed='@json($employedStatusCodes ?? [])'>
                    <label for="status-modal-decided-job" class="block text-sm font-semibold text-slate-700">就業する職種<span class="ml-1 text-xs font-normal text-slate-500">（就業決定時のみ必須）</span></label>
                    <select id="status-modal-decided-job" name="decided_job" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">選択してください</option>
                        @foreach ($jobCategories as $jobCategory)
                            <option value="{{ $jobCategory->id }}">{{ $jobCategory->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500">ステータスが「就業決定」の場合に必ず選択してください。</p>
                </div>
                <div id="status-form-error" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-600"></div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50" data-modal-dismiss>キャンセル</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-500" data-loading-text="更新中...">更新する</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterClearButton = document.querySelector('[data-filter-clear]');
            if (filterClearButton) {
                filterClearButton.addEventListener('click', () => {
                    const url = filterClearButton.dataset.filterClear;
                    const form = filterClearButton.closest('form');
                    if (form) {
                        form.reset();
                    }
                    if (url) {
                        window.location.href = url;
                    }
                });
            }

            const modal = document.getElementById('status-modal');
            const form = document.getElementById('status-form');
            const select = document.getElementById('status-modal-select');
            const decidedWrapper = document.querySelector('[data-modal-decided-job]');
            const decidedSelect = decidedWrapper ? decidedWrapper.querySelector('select') : null;
            const employedStatusCodes = (() => {
                if (!decidedWrapper) {
                    return [];
                }

                try {
                    const raw = decidedWrapper.dataset.statusEmployed || '[]';
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) {
                        return parsed.map((code) => String(code).toLowerCase());
                    }
                } catch (error) {
                    console.warn('Failed to parse employed status codes', error);
                }

                return [];
            })();
            const escapeHtml = (value) => {
                if (value == null) {
                    return '';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };
            const candidateNameEl = document.getElementById('status-modal-candidate-name');
            const errorBox = document.getElementById('status-form-error');
            const dismissButtons = modal.querySelectorAll('[data-modal-dismiss]');
            const overlay = modal.querySelector('[data-modal-overlay]');
            let activeButton = null;
            let activeRow = null;

            const updateDecidedJobState = () => {
                if (!decidedWrapper || !decidedSelect) {
                    return;
                }

                const isEmployed = employedStatusCodes.includes((select.value || '').toLowerCase());
                decidedSelect.disabled = !isEmployed;
                decidedSelect.required = isEmployed;

                if (!isEmployed) {
                    decidedSelect.value = '';
                }
            };

            const openModal = (button) => {
                activeButton = button;
                const candidateId = button.dataset.candidateId;
                activeRow = document.querySelector(`[data-candidate-row="${candidateId}"]`);
                form.setAttribute('action', button.dataset.action);
                select.value = button.dataset.currentStatus || '';
                if (decidedSelect) {
                    const currentDecided = button.dataset.currentDecidedJob || '';
                    decidedSelect.value = currentDecided;
                }
                updateDecidedJobState();
                candidateNameEl.textContent = button.dataset.candidateName || '';
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                select.focus({ preventScroll: true });
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
                activeButton = null;
                activeRow = null;
                form.reset();
                if (decidedSelect) {
                    decidedSelect.value = '';
                }
                updateDecidedJobState();
            };

            document.querySelectorAll('[data-role="status-open"]').forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });

            dismissButtons.forEach((button) => {
                button.addEventListener('click', () => closeModal());
            });

            overlay.addEventListener('click', closeModal);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            select.addEventListener('change', updateDecidedJobState);

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!activeButton || !activeRow) {
                    return;
                }

                errorBox.classList.add('hidden');
                errorBox.textContent = '';

                const submitButton = form.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.textContent = submitButton.dataset.loadingText || '更新中...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        const messages = errorData?.errors ? Object.values(errorData.errors).flat() : [errorData.message || '更新に失敗しました。'];
                        errorBox.textContent = messages.join('\n');
                        errorBox.classList.remove('hidden');
                        return;
                    }

                    const data = await response.json();

                    const statusBadge = activeRow.querySelector('[data-role="status-label"]');
                    const statusDateCell = activeRow.querySelector('[data-role="status-changed-on"]');
                    const decidedJobCell = activeRow.querySelector('[data-role="decided-job"]');

                    if (statusBadge) {
                        const badgeText = statusBadge.querySelector('[data-role="status-text"]');
                        if (badgeText) {
                            badgeText.textContent = data.status_label ?? 'ステータス未設定';
                        } else {
                            statusBadge.textContent = data.status_label ?? 'ステータス未設定';
                        }

                        if (data.status_color) {
                            const baseColor = data.status_color;
                            statusBadge.style.backgroundImage = `linear-gradient(135deg, ${baseColor}cc, ${baseColor}99)`;
                            statusBadge.style.borderColor = `${baseColor}80`;
                            statusBadge.style.setProperty('--tw-ring-color', `${baseColor}80`);
                        }
                    }

                    if (statusDateCell) {
                        statusDateCell.textContent = data.status_changed_on ?? '—';
                    }

                    activeButton.dataset.currentStatus = data.status_code ?? '';
                    activeButton.dataset.currentDecidedJob = data.decided_job_id ?? '';

                    if (decidedJobCell) {
                        const isEmployedStatus = employedStatusCodes.includes((data.status_code || '').toLowerCase());

                        if (isEmployedStatus) {
                            const decidedLabel = escapeHtml(data.decided_job_label ?? '未設定');
                            decidedJobCell.innerHTML = `<span class="inline-flex min-h-[2.25rem] items-center rounded-xl bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">${decidedLabel}</span>`;
                        } else {
                            decidedJobCell.innerHTML = '<span class="text-slate-400">—</span>';
                        }
                    }

                    closeModal();
                } catch (error) {
                    errorBox.textContent = 'ネットワークエラーが発生しました。時間を置いて再度お試しください。';
                    errorBox.classList.remove('hidden');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            });
        });
    </script>
@endsection
