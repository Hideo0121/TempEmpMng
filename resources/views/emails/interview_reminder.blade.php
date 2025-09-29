@component('mail::message')
# 職場見学リマインドのお知らせ

{{ $slotLabel }} をお送りします。

@component('mail::panel')
- 候補者: **{{ $candidate->name }}**（{{ $candidate->name_kana }}）
- 派遣会社: {{ optional($agency)->name ?? '未設定' }}
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
