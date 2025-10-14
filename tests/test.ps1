# .env に合わせて設定してください
$clientId       = 'UM9ZfJ3N0b4BLqqrHblv'
$clientSecret   = 'Fzuyp5D3pu'
$scope          = 'calendar'          # 必要に応じて調整
$assertion      = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJVTTlaZkozTjBiNEJMcXFySGJsdiIsInN1YiI6InhpNGwxLnNlcnZpY2VhY2NvdW50QHNlaWhvdW9zYWthIiwiYXVkIjoiaHR0cHM6Ly9hdXRoLndvcmtzbW9iaWxlLmNvbS9vYXV0aDIvdjIuMC90b2tlbiIsImlhdCI6MTc2MDQzNDIzNSwiZXhwIjoxNzYwNDM0ODM1LCJqdGkiOiJhNTBjMDM3Yi0wMTc0LTQ1NzUtYjA5Yy04NDI5NWE3YjBjOTQiLCJzY29wZSI6ImNhbGVuZGFyIn0.aExGFIYYjKXzGvdMB54lKc057UkjcVgE2pyIPWrHxHYrk9RPJd92HJYt_GUKuCjtIQRa2k34cyJZIIAaIdM72bFO6FhKwlr04L5kOvpUIeRQhGGqBDO8BPE2LEyn37WscfFU11RmeLNjtw3kcLekl4KD2P0dDyAKH-TsrcTHU91YiU-7n4P6w6NODHdVhlekEbdrGYPJshvn2TVjSh7GxM57rFcz7jyq2HuzQ_9VnpDYpBd85tiTqt5Bc3cOnOdTijPISadFDy8CRKIC9r1x9FW3uw8WKjEX89-O7xvI2x7zYyMcDCxVRhvYZ57x1NW6Ur7oeTSktMEx0JPcVcajOg'
$userId         = 'kasai@seihouosaka'
$calendarId     = ''

$tokenResponse = curl.exe -s -X POST 'https://auth.worksmobile.com/oauth2/v2.0/token' `
  -H 'Content-Type: application/x-www-form-urlencoded' `
  --data-urlencode "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer" `
  --data-urlencode "assertion=$assertion" `
  --data-urlencode "client_id=$clientId" `
  --data-urlencode "client_secret=$clientSecret" `
  --data-urlencode "scope=$scope"

$accessToken = ($tokenResponse | ConvertFrom-Json).access_token

$bodyJson = @{
  eventComponents = @(
    @{
      summary = 'APIテスト'
      start   = @{
        dateTime = '2025-10-15T10:00:00+09:00'
        timeZone = 'Asia/Tokyo'
      }
      end     = @{
        dateTime = '2025-10-15T11:00:00+09:00'
        timeZone = 'Asia/Tokyo'
      }
    }
  )
} | ConvertTo-Json -Depth 5

$encodedUserId = [uri]::EscapeDataString($userId)

$endpoint = if ([string]::IsNullOrEmpty($calendarId)) {
  "https://www.worksapis.com/v1.0/users/$encodedUserId/calendar/events"
} else {
  $encodedCalendarId = [uri]::EscapeDataString($calendarId)
  "https://www.worksapis.com/v1.0/users/$encodedUserId/calendars/$encodedCalendarId/events"
}

$accessToken
$endpoint


curl.exe -X POST $endpoint `
  -H "Authorization: Bearer $accessToken" `
  -H "Content-Type: application/json" `
  --data-raw $bodyJson        # ← 文字列を直接渡す
