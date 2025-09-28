@component('mail::message')
# メール送信テストのご案内

{{ $user->name }} さん

{{ $appName }} からのメール送信テストです。設定が正しく行われていれば、このメールが受信されています。

- 送信先アドレス: {{ $user->email }}
- 送信日時: {{ $now->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') }} ({{ strtoupper(config('app.timezone')) }})
- 実行環境: {{ $env }}

## 受信に問題がある場合
- 迷惑メールフォルダに振り分けられていないか確認してください。
- 受信拒否設定やドメイン制限がないかご確認ください。
- システム管理者に SMTP 設定を再確認いただくか、ログに出力されるエラーメッセージを共有してください。

@component('mail::button', ['url' => config('app.url')])
{{ $appName }} にアクセス
@endcomponent

引き続きよろしくお願いいたします。

@endcomponent
