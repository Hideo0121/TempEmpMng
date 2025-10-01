@component('mail::message')
# 職場見学リマインドのお知らせ

{{ $slotLabel }} をお送りします。

@component('mail::panel')
@php
$wishJobs = [
    ['label' => '希望職種①', 'value' => optional($candidate->wishJob1)->name],
    ['label' => '希望職種②', 'value' => optional($candidate->wishJob2)->name],
    ['label' => '希望職種③', 'value' => optional($candidate->wishJob3)->name],
];
@endphp
- 候補者: **{{ $candidate->name }}**（{{ $candidate->name_kana }}）
- 派遣会社: {{ optional($agency)->name ?? '未設定' }}
- 希望職種①: {{ $wishJobs[0]['value'] ?? '未設定' }}
@foreach (array_slice($wishJobs, 1) as $job)
@continue(!filled($job['value']))
- {{ $job['label'] }}: {{ $job['value'] }}
@endforeach
- 見学日時: {{ $scheduledAt->format('Y/m/d H:i') }} （{{ $timezone }}）
@endcomponent

## 対応者
@foreach ($handlers as $index => $handler)
- 対応者{{ $index + 1 }}: {{ $handler->name }} &lt;{{ $handler->email }}&gt;
@endforeach

@if ($owner)
- 登録担当: {{ $owner->name }} &lt;{{ $owner->email }}&gt;
@endif

@if ($memo)
## 補足メモ
{{ $memo }}
@endif

@if ($slot === 'thirty_minutes' && (! $isThirtyMinutesEnabledForInterview || $isThirtyMinutesGloballyDisabled))
> ※ 30分前リマインドは現在停止設定になっています。手動での確認をお願いします。
@endif

よろしくお願いいたします。

@endcomponent
