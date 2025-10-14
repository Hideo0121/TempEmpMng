<#
.SYNOPSIS
  固定パラメータで LINE WORKS カレンダーに予定を追加する PowerShell スクリプト
  JWT 生成 → トークン取得 → イベント登録（個人 / 共有カレンダー）まで自動実行。

.REQUIREMENTS
  - PowerShell 7.2+（RSA.ImportFromPem を使用）
  - LINE WORKS Developer Console: ClientId / ClientSecret / ServiceAccount / PrivateKey(PEM)
  - スコープ: calendar
#>

# =====================================================
# 設定セクション（ここだけ編集してください）
# =====================================================

# --- 認証 ---
$ClientId       = "UM9ZfJ3N0b4BLqqrHblv"         # Client ID
$ClientSecret   = "Fzuyp5D3pu"         # Client Secret
$ServiceAccount = "xi4l1.serviceaccount@seihouosaka"            # Service Account ID
$PrivateKeyPath = "C:\xampp\htdocs\TempEmpMng\ShPrg\lw.pem"         # PEMファイルパス（または下の$PrivateKeyPemを使用）

# --- 予定の対象ユーザー（このユーザーがアクセス権を持つ共有カレンダーに書き込みます）---
$UserId       = "kasai@seihouosaka"                  # 対象ユーザー

# --- 共有カレンダー指定 ---
# どちらか1つを使います。CalendarId を優先（※不明な場合は CalendarName で部分一致→自動解決）
$CalendarId    = "c_400446517_a6b0f0f6-59b4-4001-8704-a90a90ab195e"  # ← 共有（チーム/会社）カレンダーID（分かっていれば推奨）
$CalendarName  = "成鳳カレンダー"                                       # ← 例: "営業部共有カレンダー"（部分一致OK）
$PreferCalType = ""                                   # 任意: "team" / "company" / "user" から優先解決

# --- 予定の内容 ---
$Summary     = "営業定例"                       # 件名
$Description = "議題：月次報告"                 # 本文（任意）
$Location    = "第1会議室"                      # 場所（任意）
$Start       = "2025-10-20 09:00"               # "YYYY-MM-DD HH:mm" or ISO8601
$End         = "2025-10-20 10:00"               # 同上
$TimeZone    = "Asia/Tokyo"                     # 例: Asia/Tokyo

# --- API エンドポイント（通常変更不要）---
$AuthUrl = "https://auth.worksmobile.com/oauth2/v2.0/token"
$ApiBase = "https://www.worksapis.com/v1.0"
$Scope   = "calendar"

# --- ログ（任意）---
$EnableFileLog = $true
$LogPath       = "C:\ShPrg\LWCalendarEvent.log"

# =====================================================
# 以降、編集不要
# =====================================================

function Write-Log {
  param([string] $Message, [string] $Level = "INFO")
  $timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
  $line = "$timestamp [$($Level.ToUpper())] $Message"
  Write-Host $line
  if ($EnableFileLog) {
    $dir = Split-Path -Parent $LogPath
    if ($dir -and -not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    Add-Content -LiteralPath $LogPath -Value $line
  }
}

function ConvertTo-Base64Url([byte[]] $Bytes) {
  [Convert]::ToBase64String($Bytes).TrimEnd('=').Replace('+','-').Replace('/','_')
}

function Get-PrivateKeyPem {
  if ($PrivateKeyPath -and (Test-Path $PrivateKeyPath)) {
    return (Get-Content -LiteralPath $PrivateKeyPath -Raw) -replace "`r`n", "`n"
  }
  elseif ($PrivateKeyPem -and $PrivateKeyPem.Trim().Length -gt 0) {
    return $PrivateKeyPem -replace "`r`n", "`n"
  }
  else {
    throw "秘密鍵が指定されていません。PrivateKeyPath か PrivateKeyPem を設定してください。"
  }
}

function New-JwtAssertion {
  param(
    [string] $ClientId,
    [string] $ServiceAccount,
    [string] $AuthAudience,
    [string] $Scope,
    [string] $PrivateKeyPem
  )

  $header  = @{ alg = "RS256"; typ = "JWT" }
  $now     = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
  $payload = @{
    iss   = $ClientId
    sub   = $ServiceAccount
    aud   = $AuthAudience
    iat   = $now
    exp   = $now + 600
    jti   = [guid]::NewGuid().ToString()
    scope = $Scope
  }

  $headerB64  = ConvertTo-Base64Url ([Text.Encoding]::UTF8.GetBytes(($header  | ConvertTo-Json -Compress)))
  $payloadB64 = ConvertTo-Base64Url ([Text.Encoding]::UTF8.GetBytes(($payload | ConvertTo-Json -Compress)))
  $toSign     = [Text.Encoding]::UTF8.GetBytes("$headerB64.$payloadB64")

  $rsa = [System.Security.Cryptography.RSA]::Create()
  try {
    $rsa.ImportFromPem($PrivateKeyPem)
  } catch {
    throw "PEM の読み込みに失敗しました。PowerShell 7.2+ か、鍵フォーマット(PKCS#8 'BEGIN PRIVATE KEY') を確認してください。`n$($_.Exception.Message)"
  }

  $signature = $rsa.SignData(
    $toSign,
    [System.Security.Cryptography.HashAlgorithmName]::SHA256,
    [System.Security.Cryptography.RSASignaturePadding]::Pkcs1
  )
  $sigB64 = ConvertTo-Base64Url $signature

  return "$headerB64.$payloadB64.$sigB64"
}

function Get-LWAccessToken {
  param(
    [string] $ClientId, [string] $ClientSecret, [string] $Scope,
    [string] $AuthUrl, [string] $Assertion
  )

  $body = @{
    grant_type    = "urn:ietf:params:oauth:grant-type:jwt-bearer"
    assertion     = $Assertion
    client_id     = $ClientId
    client_secret = $ClientSecret
    scope         = $Scope
  }

  try {
    $resp = Invoke-RestMethod -Method Post -Uri $AuthUrl -Body $body -ContentType "application/x-www-form-urlencoded"
  } catch {
    throw "トークン取得に失敗しました。$($_.Exception.Message)"
  }

  if (-not $resp.access_token) {
    throw "access_token が応答にありません。応答: $( $resp | ConvertTo-Json -Depth 5 )"
  }
  return $resp.access_token
}

function Normalize-ToIsoLocal([string] $Text) {
  if ($Text -match "^\d{4}-\d{2}-\d{2}T") { return $Text }
  [DateTime]::Parse($Text).ToString("yyyy-MM-ddTHH:mm:ss")
}

function Get-LWCalendars {
  param([string] $ApiBase, [string] $AccessToken, [string] $UserId)
  $uri = "$($ApiBase.TrimEnd('/'))/users/$([uri]::EscapeDataString($UserId))/calendars"
  Invoke-RestMethod -Method Get -Uri $uri -Headers @{ Authorization = "Bearer $AccessToken" }
}

function Resolve-CalendarIdByName {
  param(
    [array]  $Calendars,        # Get-LWCalendars の結果.calendars
    [string] $Name,             # カレンダー名（部分一致OK）
    [string] $PreferType = ""   # "team" / "company" / "user" の優先タイプ（任意）
  )
  if (-not $Calendars) { throw "カレンダー一覧が空です。" }

  $matches = $Calendars | Where-Object { $_.name -like "*$Name*" }
  if ($PreferType) { $matches = $matches | Where-Object { $_.type -eq $PreferType } }

  if (-not $matches) { throw "名前 '$Name' に一致するカレンダーが見つかりません。" }
  if ($matches.Count -gt 1) {
    # isDefault / type 優先で1件に絞る
    $best = $matches | Sort-Object `
      @{Expression={($_.isDefault -eq $true)}; Descending=$true},
      @{Expression={($_.type -eq 'team')}; Descending=$true},
      @{Expression={($_.type -eq 'company')}; Descending=$true} |
      Select-Object -First 1
    return $best.calendarId
  }
  return $matches[0].calendarId
}

function New-LWCalendarEvent {
  param(
    [string] $ApiBase, [string] $AccessToken, [string] $UserId, [string] $CalendarId,
    [string] $Summary, [string] $StartIso, [string] $EndIso, [string] $TimeZone,
    [string] $Description, [string] $Location
  )

  $event = @{
    eventComponents = @(
      @{
        summary = $Summary
        start   = @{ dateTime = $StartIso; timeZone = $TimeZone }
        end     = @{ dateTime = $EndIso;   timeZone = $TimeZone }
      }
    )
  }
  if ($Description) { $event.eventComponents[0].description = $Description }
  if ($Location)    { $event.eventComponents[0].location    = $Location }

  if ($CalendarId) {
    $uri = ($ApiBase.TrimEnd('/')) + "/users/$([uri]::EscapeDataString($UserId))/calendars/$([uri]::EscapeDataString($CalendarId))/events"
  } else {
    $uri = ($ApiBase.TrimEnd('/')) + "/users/$([uri]::EscapeDataString($UserId))/calendar/events"
  }

  try {
    $resp = Invoke-RestMethod -Method Post -Uri $uri `
      -Headers @{ Authorization = "Bearer $AccessToken" } `
      -ContentType "application/json" `
      -Body ($event | ConvertTo-Json -Depth 6)
  } catch {
    throw "イベント作成に失敗しました。$($_.Exception.Message)"
  }
  return $resp
}

# =========================
# 実行フロー
# =========================
try {
  Write-Log "=== LINE WORKS カレンダー登録開始 ==="

  $pem = Get-PrivateKeyPem
  Write-Log "秘密鍵読込 OK"

  $jwt = New-JwtAssertion -ClientId $ClientId -ServiceAccount $ServiceAccount -AuthAudience $AuthUrl -Scope $Scope -PrivateKeyPem $pem
  Write-Log "JWT 作成 OK"

  $token = Get-LWAccessToken -ClientId $ClientId -ClientSecret $ClientSecret -Scope $Scope -AuthUrl $AuthUrl -Assertion $jwt
  Write-Log "アクセストークン取得 OK"

  # CalendarId が空で、CalendarName がある場合は名前から解決
  if (-not $CalendarId -and $CalendarName) {
    Write-Log "カレンダー名 '$CalendarName' から ID を解決します（PreferType='$PreferCalType'）"
    $calList = Get-LWCalendars -ApiBase $ApiBase -AccessToken $token -UserId $UserId
    $CalendarId = Resolve-CalendarIdByName -Calendars $calList.calendars -Name $CalendarName -PreferType $PreferCalType
    Write-Log "解決結果: CalendarId=$CalendarId"
  }

  $startIso = Normalize-ToIsoLocal $Start
  $endIso   = Normalize-ToIsoLocal $End
  Write-Log "時刻 正規化: $startIso ～ $endIso ($TimeZone)"

  $result = New-LWCalendarEvent -ApiBase $ApiBase -AccessToken $token -UserId $UserId -CalendarId $CalendarId `
            -Summary $Summary -StartIso $startIso -EndIso $endIso -TimeZone $TimeZone `
            -Description $Description -Location $Location

  Write-Log "登録完了" "SUCCESS"
  $result | ConvertTo-Json -Depth 8
}
catch {
  Write-Log $_.Exception.Message "ERROR"
  throw
}
finally {
  Write-Log "=== 終了 ==="
}
