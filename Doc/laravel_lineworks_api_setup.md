# LINE WORKS カレンダー API 連携 (Laravel 実装テンプレ)

## 概要
Laravel から LINE WORKS API 2.0 (カレンダー) を利用して予定登録を行う実装例です。

- Service Account (JWT) 認証方式を使用
- Laravelの`.env`で設定を管理
- GuzzleHTTP + Firebase/JWT を使用

---

## 1) ライブラリインストール
```bash
composer require guzzlehttp/guzzle:^7 firebase/php-jwt:^6
```

---

## 2) `.env` 設定
```dotenv
LW_AUTH_URL=https://auth.worksmobile.com/oauth2/v2.0/token
LW_API_BASE=https://www.worksapis.com/v1.0
LW_CLIENT_ID=xxxxxxxxxxxxxxxxxxxxxxxx
LW_CLIENT_SECRET=yyyyyyyyyyyyyyyyyyyyyyyyyyyy
LW_SERVICE_ACCOUNT=sa-1234567@xxx
LW_SCOPE=calendar
LW_PRIVATE_KEY_PEM="-----BEGIN PRIVATE KEY-----\nMIIEv....\n-----END PRIVATE KEY-----\n"
LW_JWT_ALG=RS256
LW_TZ=Asia/Tokyo
LW_CALENDAR_ID=
LW_CALENDAR_NAME=
LW_CALENDAR_PREFER_TYPE=
LW_CALENDAR_USER_ID=
LW_RETRY_ATTEMPTS=3
LW_RETRY_DELAY_MS=250
LW_CA_BUNDLE_PATH=
LW_VERIFY_SSL=true
```

> PEMをファイルで持ちたい場合、`storage/app/lineworks/private_key.pem`に保存し、`.env`に`LW_PRIVATE_KEY_PATH=storage/app/lineworks/private_key.pem`を追加。

> Windows Server などで社内プロキシや自己署名証明書を経由する場合は、信頼済みルート証明書を `LW_CA_BUNDLE_PATH` に指定してください（例: `storage/app/lineworks/ca_bundle.pem`）。既定では Laravel 側で `php.ini` の `curl.cainfo` を参照します。

> テストや障害対応で一時的に証明書検証を無効化したい場合は `LW_VERIFY_SSL=false` を指定できます（本番環境での恒常運用は非推奨）。

> `LW_PRIVATE_KEY_PATH` は絶対パス（例: `C:\Secrets\lineworks.pem`）でも指定できます。

> JWT の `aud` クレームには `LW_AUTH_URL` のスキーム + ホスト（+ポート）が自動で使用されるため、`/oauth2/v2.0/token` のようにパス付きで設定しても問題ありません。

> `LW_CALENDAR_ID` が未設定の場合は、`LW_CALENDAR_NAME`（部分一致）で自動解決します。`LW_CALENDAR_PREFER_TYPE` を `team` / `company` / `user` のいずれかに設定すると該当タイプを優先します。

> イベント登録で `SERVICE_UNAVAILABLE` が返ってきた場合、自動でリトライします。回数や待機時間を調整したい場合は `LW_RETRY_ATTEMPTS` と `LW_RETRY_DELAY_MS` を使用してください。

> LINE WORKS 側が長時間応答しない場合は、`RegisterLineworksInterviewEvent` ジョブが `reminders` キューに投入され、バックグラウンドで再登録を試みます。`php artisan queue:work --queue=reminders` を常駐させていることを確認してください。

---

## 3) `config/services.php`に追記
```php
'lineworks' => [
    'auth_url'        => env('LW_AUTH_URL'),
    'api_base'        => env('LW_API_BASE'),
    'client_id'       => env('LW_CLIENT_ID'),
    'client_secret'   => env('LW_CLIENT_SECRET'),
    'service_account' => env('LW_SERVICE_ACCOUNT'),
    'scope'           => env('LW_SCOPE', 'calendar'),
    'private_key_pem' => env('LW_PRIVATE_KEY_PEM'),
    'private_key_path'=> env('LW_PRIVATE_KEY_PATH'),
    'jwt_alg'         => env('LW_JWT_ALG', 'RS256'),
    'default_tz'      => env('LW_TZ', 'Asia/Tokyo'),
],
```

---

## 4) `app/Services/LineworksCalendar.php`
JWT 生成→トークン取得→予定登録 を担当するサービスクラス

```php
<?php
namespace App\Services;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LineworksCalendar
{
    private Client $http;
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('services.lineworks');
        $this->http = new Client(['timeout' => 15]);
    }

    public function accessToken(): string
    {
        $cacheKey = 'lw_token_' . md5($this->cfg['client_id']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $jwt = $this->buildClientAssertion();
            $resp = $this->http->post($this->cfg['auth_url'], [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                    'client_id'  => $this->cfg['client_id'],
                    'client_secret' => $this->cfg['client_secret'],
                    'scope'      => $this->cfg['scope'],
                ],
            ]);

            $json = json_decode((string)$resp->getBody(), true);
            return $json['access_token'];
        });
    }

    public function createEvent(string $userId, array $event): array
    {
        $token = $this->accessToken();
        $url = rtrim($this->cfg['api_base'], '/') . '/users/' . rawurlencode($userId) . '/calendar/events';
        $resp = $this->http->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type'  => 'application/json',
            ],
            'json' => ['eventComponents' => [$event]],
        ]);
        return json_decode((string)$resp->getBody(), true);
    }

    public function makeEvent(string $summary, string $start, string $end, array $opts = []): array
    {
        $tz = $opts['timeZone'] ?? $this->cfg['default_tz'];
        return array_merge([
            'summary' => $summary,
            'start' => ['dateTime' => $start, 'timeZone' => $tz],
            'end' => ['dateTime' => $end, 'timeZone' => $tz],
        ], $opts);
    }

    private function buildClientAssertion(): string
    {
        $now = time();
        $payload = [
            'iss' => $this->cfg['client_id'],
            'sub' => $this->cfg['service_account'],
            'aud' => $this->cfg['auth_url'],
            'iat' => $now,
            'exp' => $now + 600,
            'jti' => (string) Str::uuid(),
            'scope' => $this->cfg['scope'],
        ];
        $key = $this->loadPrivateKey();
        return JWT::encode($payload, $key, $this->cfg['jwt_alg']);
    }

    private function loadPrivateKey(): string
    {
        if (!empty($this->cfg['private_key_pem'])) {
            return str_replace("\\n", "\n", $this->cfg['private_key_pem']);
        }
        if (!empty($this->cfg['private_key_path'])) {
            return file_get_contents(base_path($this->cfg['private_key_path']));
        }
        throw new \RuntimeException('LINE WORKS private key not configured');
    }
}
```

---

## 5) コントローラ & ルート
```php
// app/Http/Controllers/LineworksCalendarController.php
namespace App\Http\Controllers;

use App\Services\LineworksCalendar;
use Illuminate\Http\Request;

class LineworksCalendarController extends Controller
{
    public function createSample(Request $req, LineworksCalendar $lw)
    {
        $userId = $req->input('userId', 'user@example.com');
        $event = $lw->makeEvent(
            'MTG', '2025-10-15T10:00:00', '2025-10-15T11:00:00',
            ['description' => 'LINE WORKS API test', 'location' => 'Room A']
        );
        return response()->json($lw->createEvent($userId, $event));
    }
}
```

```php
// routes/web.php
use App\Http\Controllers\LineworksCalendarController;
Route::post('/lineworks/events/sample', [LineworksCalendarController::class, 'createSample']);
```

---

## 6) 動作テスト
```bash
curl -X POST http://localhost:8000/lineworks/events/sample \
  -H "Content-Type: application/json" \
  -d '{"userId":"your-user@example.com"}'
```

---

## 7) 運用メモ
- `/users/{userId}` にはメールアドレスまたは userId を使用
- Scope は `calendar`を設定
- Token は 50分間キャッシュ
- ISO8601 + JST指定 (Asia/Tokyo)
- `createEventOnCalendar()` で特定カレンダーIDへ登録も可

---

## 8) 障害事例と対処履歴
### 2025-10-14 LINE WORKS 予定登録エラー
**症状**
- LINE WORKS API から `SERVICE_UNAVAILABLE` が返り、予定が登録されない。
- Laravel 側のリクエストログには HTTP 503 とエラーメッセージのみが残る。


### 2025-10-15 Start time not set 応答
**症状**
- API から `INVALID_PARAMETER` (`Start time not set`) が返され、予定の作成が拒否される。
- リクエスト payload の `start.dateTime` / `end.dateTime` は `Y-m-d\\TH:i:s` 形式で送信しており、`timeZone` フィールドには IANA 形式 (`Asia/Tokyo` など) を指定している。

**原因/推測**
- LINE WORKS 側が日付文字列に明示的な UTC オフセットを要求するケースがある。
- 同一 payload でも成功する環境があることから、バリデーションがタイミングやテナント条件で変動している可能性がある。

**対応**
- `LineworksCalendarService::createEvent()` で `Start time not set` を検知した際に、該当イベントの `dateTime` へ `+09:00` などの UTC オフセットを付与して自動リトライするフォールバックを導入。
- 通常フローでは従来通り `Y-m-d\TH:i:s` 形式を維持し、必要時のみ追加のリトライを発動。
- フォールバック適用時は警告ログを出力し、調査時に切り分けできるようにした。

**教訓**
- バリデーションエラーのメッセージを解析し、フェイルセーフの再送戦略を用意しておくと復旧が早まる。
- API 仕様が曖昧な場合でも、UTC オフセット付与など一般的な ISO8601 形式を予備手段として持っておくと安心。

### 2025-10-14 Windows Server + IIS での SSL チェーンエラー
**症状**
- 本番環境 (Windows Server + IIS) で `LINE WORKSアクセストークンの取得に失敗しました。` と出力され、`cURL error 60: SSL certificate problem: self-signed certificate in certificate chain` がログに記録される。

**原因**
- サーバーが社内プロキシ経由で外部通信を行っており、応答に社内 CA の自己署名証明書が含まれていた。
- PHP (cURL) が参照している CA バンドルに社内 CA が含まれていないため、証明書検証が失敗していた。

**対応**
- 社内 CA の証明書を Base64 (PEM) 形式でエクスポートし、アプリケーションサーバーの `storage/app/lineworks/ca_bundle.pem` に配置。
- `.env` に `LW_CA_BUNDLE_PATH=storage/app/lineworks/ca_bundle.pem` を設定し、`php artisan config:clear` を実行。
- 必要に応じて `php.ini` の `curl.cainfo` にも同じバンドルを指定して cURL 全体で共有する。

**教訓**
- Windows + IIS 環境では PHP の cURL が OS 証明書ストアを自動参照しないため、社内 CA を PEM 形式で明示的に渡す設計を用意しておく。
- 証明書検証を無効化する代わりに、信頼済み証明書をアプリ側で管理する方が安全。
**原因**
1. `start / end` の `dateTime` フィールドが `YYYY-MM-DD HH:mm:ss` 形式になっており、API が要求する `YYYY-MM-DDTHH:mm:ss` から外れていた。
2. 入力文字列のタイムゾーン正規化が不十分で、`Asia/Tokyo` 想定の日時が UTC 解釈されるケースがあった。
3. イベント生成時にオプション配列を素のまま `array_merge` していたため、`timeZone` など API が解釈しないキーがトップレベルに混入し、payload が不正になっていた。

**対応**
- PowerShell 検証スクリプトと同じ処理に合わせ、`makeEvent()` 内で `normalizeEventDateTime()` を実装。Carbon / 文字列どちらの入力でも `Asia/Tokyo` に揃えた `YYYY-MM-DDTHH:mm:ss` を生成するよう修正。
- イベント配列に含めるキーを `summary` / `start` / `end` / `description` / `location` のみに限定し、不要なオプションは除去。
- 対応後に `phpunit --filter LineworksCalendarServiceTest` を実行し、10件のテストが成功することを確認。

**教訓**
- PowerShell 版など既存の成功例と API payload を精査し、フォーマット差分を必ず吸収する。
- 日時を受け渡す処理では、入力源を問わずタイムゾーンと ISO8601 形式を強制するヘルパーを用意しておく。
- オプション配列をそのまま JSON に流し込むのではなく、API 仕様に沿った構造へ整形する。

### 2025-10-17 正常登録時のログ出力
**背景**
- LINE WORKS カレンダーへの登録が成功した場合にも、運用チームが操作履歴を追跡できるようログ出力を強化。

**出力内容**
- ログレベル: `info`
- メッセージ: `LINE WORKSカレンダーへの登録が正常に完了しました。`
- 主要コンテキスト: `user_id`, `calendar_id`, `calendar_name`, `event_summary`, `event_start`, `event_end`, `payload_json` (送信 JSON)、`status` (HTTP ステータス)、`event_id` (レスポンスに含まれる場合)、`response_json` (レスポンス JSON)

**備考**
- `payload_json` / `response_json` は改行や日本語を保持したまま JSON 文字列化されるため、障害調査時にそのまま `jq` などへ渡して解析可能。
- 正常系を含めたログが揃うことで、直近の登録状況を Kibana 等から時系列で把握しやすくなる。

