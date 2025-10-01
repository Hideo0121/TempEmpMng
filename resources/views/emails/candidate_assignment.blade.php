@component('mail::message')
# {{ $isUpdate ? '紹介者情報が更新されました' : '新しい紹介者が登録されました' }}

{{ $recipient->name }} さん

{{ $candidate->name }} さんの紹介者情報が{{ $actionLabel }}されました。詳細は以下をご確認ください。

@component('mail::panel')
@php
$wishJobs = [
    ['label' => '希望職種①', 'value' => optional($candidate->wishJob1)->name],
    ['label' => '希望職種②', 'value' => optional($candidate->wishJob2)->name],
    ['label' => '希望職種③', 'value' => optional($candidate->wishJob3)->name],
];
@endphp
- 候補者: **{{ $candidate->name }}**（{{ $candidate->name_kana }}）
- 紹介日: {{ optional($candidate->introduced_on)->format('Y/m/d') ?? '未設定' }}
- 派遣会社: {{ optional($candidate->agency)->name ?? '未設定' }}
- 希望職種①: {{ $wishJobs[0]['value'] ?? '未設定' }}
@foreach (array_slice($wishJobs, 1) as $job)
@continue(!filled($job['value']))
- {{ $job['label'] }}: {{ $job['value'] }}
@endforeach
- 現在ステータス: {{ optional($candidate->status)->label ?? '未設定' }}
@endcomponent

@if ($triggeredBy)
職場見学対応: {{ $triggeredBy->name }} &lt;{{ $triggeredBy->email }}&gt;
@endif

@component('mail::button', ['url' => $candidateUrl])
候補者詳細を確認する
@endcomponent

引き続きよろしくお願いいたします。

@endcomponent
