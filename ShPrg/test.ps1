<#
.SYNOPSIS
  固定パラメータで LINE WORKS カレンダーに予定を追加する PowerShell スクリプト
  JWT生成→トークン取得→イベント登録まで自動実行。

.REQUIREMENTS
  - PowerShell 7.2+（RSA.ImportFromPemが必要）
  - LINE WORKS Developer Console で発行した Service Account 情報と PEM 鍵
#>

# =====================================================
# 設定セクション（ここだけ編集してください）
# =====================================================
$ClientId       = "UM9ZfJ3N0b4BLqqrHblv"         # Client ID
$ClientSecret   = "Fzuyp5D3pu"         # Client Secret
$ServiceAccount = "xi4l1.serviceaccount@seihouosaka"            # Service Account ID
$PrivateKeyPath = "C:\ShPrg\lw.pem"         # PEMファイルパス（または下の$PrivateKeyPemを使用）

# イベント情報
$UserId       = "kasai@seihouosaka"                   # 対象ユーザー
$CalendarId   = ""                                   # 共有カレンダーID（空ならマイカレンダー）
$Summary      = "テスト予定"                         # 件名
$Description  = "LINE WORKS API PowerShellテスト"     # 内容
$Location     = "会議室A"                            # 場所
$Start        = "2025-10-15 10:00"                   # 開始時刻
$End          = "2025-10-15 11:00"                   # 終了時刻
$TimeZone     = "Asia/Tokyo"                         # タイムゾーン

# API設定（変更不要）
$AuthUrl = "https://auth.worksmobile.com/oauth2/v2.0/token"
$ApiBase = "https://www.worksapis.com/v1.0"
$Scope   = "calendar"
# =====================================================

function ConvertTo-Base64Url([byte[]] $Bytes) {
  [Convert]::ToBase64String($Bytes).TrimEnd('=').Replace('+','-').Replace('/','_')
}

function Get-PrivateKeyPem {
  if ($PrivateKeyPath -and (Test-Path $PrivateKeyPath)) {
    return (Get-Content -LiteralPath $PrivateKeyPath -Raw) -replace "`r`n", "`n"
  }
  elseif ($PrivateKeyPem) { return $PrivateKeyPem -replace "`r`n", "`n" }
  else { throw "秘密鍵が指定されていません。" }
}

function New-JwtAssertion {
  param($ClientId, $ServiceAccount, $AuthAudience, $Scope, $PrivateKeyPem)
  $header  = @{ alg = "RS256"; typ = "JWT" }
  $now     = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
  $payload = @{
    iss = $ClientId; sub = $ServiceAccount; aud = $AuthAudience
    iat = $now; exp = $now + 600; jti = [guid]::NewGuid().ToString(); scope = $Scope
  }
  $headerB64  = ConvertTo-Base64Url ([Text.Encoding]::UTF8.GetBytes(($header | ConvertTo-Json -Compress)))
  $payloadB64 = ConvertTo-Base64Url ([Text.Encoding]::UTF8.GetBytes(($payload | ConvertTo-Json -Compress)))
  $toSign     = [Text.Encoding]::UTF8.GetBytes("$headerB64.$payloadB64")
  $rsa = [System.Security.Cryptography.RSA]::Create()
  $rsa.ImportFromPem($PrivateKeyPem)
  $sigB64 = ConvertTo-Base64Url ($rsa.SignData($toSign,
    [System.Security.Cryptography.HashAlgorithmName]::SHA256,
    [System.Security.Cryptography.RSASignaturePadding]::Pkcs1))
  return "$headerB64.$payloadB64.$sigB64"
}

function Get-LWAccessToken($ClientId, $ClientSecret, $Scope, $AuthUrl, $Assertion) {
  $body = @{
    grant_type    = "urn:ietf:params:oauth:grant-type:jwt-bearer"
    assertion     = $Assertion
    client_id     = $ClientId
    client_secret = $ClientSecret
    scope         = $Scope
  }
  $resp = Invoke-RestMethod -Method Post -Uri $AuthUrl -Body $body -ContentType "application/x-www-form-urlencoded"
  return $resp.access_token
}

function Normalize-ToIsoLocal($Text) {
  if ($Text -match "^\d{4}-\d{2}-\d{2}T") { return $Text }
  [DateTime]::Parse($Text).ToString("yyyy-MM-ddTHH:mm:ss")
}

function New-LWCalendarEvent($ApiBase, $AccessToken, $UserId, $CalendarId, $Summary, $StartIso, $EndIso, $TimeZone, $Description, $Location) {
  $event = @{
    eventComponents = @(@{
      summary = $Summary
      start = @{ dateTime = $StartIso; timeZone = $TimeZone }
      end   = @{ dateTime = $EndIso;   timeZone = $TimeZone }
    })
  }
  if ($Description) { $event.eventComponents[0].description = $Description }
  if ($Location)    { $event.eventComponents[0].location = $Location }

  if ($CalendarId) {
    $uri = "$ApiBase/users/$([uri]::EscapeDataString($UserId))/calendars/$([uri]::EscapeDataString($CalendarId))/events"
  } else {
    $uri = "$ApiBase/users/$([uri]::EscapeDataString($UserId))/calendar/events"
  }

  Invoke-RestMethod -Method Post -Uri $uri `
    -Headers @{ Authorization = "Bearer $AccessToken" } `
    -ContentType "application/json" `
    -Body ($event | ConvertTo-Json -Depth 6)
}

try {
  Write-Host "=== LINE WORKS カレンダー登録 ===" -ForegroundColor Cyan
  $pem = Get-PrivateKeyPem
  $jwt = New-JwtAssertion $ClientId $ServiceAccount $AuthUrl $Scope $pem
  $token = Get-LWAccessToken $ClientId $ClientSecret $Scope $AuthUrl $jwt
  $startIso = Normalize-ToIsoLocal $Start
  $endIso   = Normalize-ToIsoLocal $End
  $result = New-LWCalendarEvent $ApiBase $token $UserId $CalendarId $Summary $startIso $endIso $TimeZone $Description $Location
  Write-Host "`n✅ 登録完了：" -ForegroundColor Green
  $result | ConvertTo-Json -Depth 8
}
catch {
  Write-Host "`n❌ エラー：" -ForegroundColor Red
  Write-Host $_.Exception.Message
}
