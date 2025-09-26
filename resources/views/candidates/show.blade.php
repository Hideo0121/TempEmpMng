@extends('layouts.app')

@section('pageTitle', '紹介者詳細')
@section('pageDescription', '候補者の基本情報と見学スケジュールを確認できます。')

@section('content')
    <section class="space-y-6">
        <article class="rounded-3xl bg-white p-6 shadow-md">
            <header class="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ $candidate->name }} <span class="ml-2 text-sm text-slate-500">({{ $candidate->name_kana }})</span></h2>
                    <p class="text-sm text-slate-600">紹介日: {{ optional($candidate->introduced_on)->format('Y/m/d') }}</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-blue-100 px-4 py-1 text-sm font-semibold text-blue-700">
                    {{ optional($candidate->status)->label ?? 'ステータス未設定' }}
                </span>
            </header>

            <dl class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-sm font-semibold text-slate-600">派遣会社</dt>
                    <dd class="mt-1 text-base text-slate-800">{{ optional($candidate->agency)->name ?? '未設定' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-600">対応者</dt>
                    <dd class="mt-1 text-base text-slate-800">
                        @php
                            $handlers = collect([$candidate->handler1, $candidate->handler2])->filter();
                        @endphp
                        @if ($handlers->isEmpty())
                            未設定
                        @else
                            {{ $handlers->pluck('name')->implode(' / ') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-600">希望職種</dt>
                    <dd class="mt-1 text-base text-slate-800">
                        @php
                            $jobPreferences = collect([
                                ['label' => '第1希望', 'value' => optional($candidate->wishJob1)->name],
                                ['label' => '第2希望', 'value' => optional($candidate->wishJob2)->name],
                                ['label' => '第3希望', 'value' => optional($candidate->wishJob3)->name],
                            ])->filter(fn ($item) => filled($item['value']));
                        @endphp
                        @if ($jobPreferences->isEmpty())
                            <span class="text-sm text-slate-500">希望職種は未設定です。</span>
                        @else
                            <ul class="list-disc space-y-1 pl-5 text-sm">
                                @foreach ($jobPreferences as $job)
                                    <li>{{ $job['label'] }}: {{ $job['value'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-600">交通費</dt>
                    <dd class="mt-1 text-base text-slate-800">
                        日額: {{ $candidate->transport_cost_day ? number_format($candidate->transport_cost_day) . ' 円' : '未設定' }}<br>
                        月額: {{ $candidate->transport_cost_month ? number_format($candidate->transport_cost_month) . ' 円' : '未設定' }}
                    </dd>
                </div>
            </dl>
        </article>

        <article class="rounded-3xl bg-white p-6 shadow-md">
            <h3 class="text-lg font-semibold text-slate-900">見学スケジュール</h3>
            <table class="mt-4 min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2">予定日時</th>
                        <th class="px-4 py-2">場所</th>
                        <th class="px-4 py-2">備考</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($candidate->interviews as $interview)
                        <tr>
                            <td class="px-4 py-2">{{ optional($interview->scheduled_at)->format('Y/m/d H:i') ?? '未設定' }}</td>
                            <td class="px-4 py-2">{{ $interview->place ?? '未設定' }}</td>
                            <td class="px-4 py-2">{{ $interview->memo ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center text-slate-500">見学スケジュールはまだ登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </article>
    </section>
@endsection
