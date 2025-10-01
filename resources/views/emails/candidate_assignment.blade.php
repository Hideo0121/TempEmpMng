@component('mail::message')
# {{ $isUpdate ? '紹介者情報が更新されました' : '新しい紹介者が登録されました' }}

{{ $recipient->name }} さん

{{ $candidate->name }} さんの紹介者情報が{{ $actionLabel }}されました。詳細は以下をご確認ください。

@component('mail::panel')
- 候補者: **{{ $candidate->name }}**（{{ $candidate->name_kana }}）
- 紹介日: {{ optional($candidate->introduced_on)->format('Y/m/d') ?? '未設定' }}
- 派遣会社: {{ optional($candidate->agency)->name ?? '未設定' }}
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
