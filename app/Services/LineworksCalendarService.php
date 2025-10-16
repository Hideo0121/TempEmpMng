<?php

namespace App\Services;

use App\Exceptions\LineworksServiceUnavailableException;
use App\Models\Candidate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class LineworksCalendarService
{
    private Client $client;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(?Client $client = null)
    {
        $this->config = config('services.lineworks', []);
        $this->client = $client ?? new Client($this->makeClientOptions());
    }

    public function isConfigured(): bool
    {
        if (!($this->config['enabled'] ?? false)) {
            return false;
        }

        $required = ['auth_url', 'api_base', 'client_id', 'client_secret', 'service_account'];

        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                return false;
            }
        }

        if (empty($this->config['private_key_pem']) && empty($this->config['private_key_path'])) {
            return false;
        }

        return true;
    }

    public function createInterviewEvent(Candidate $candidate, Carbon $scheduledAt): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('LINE WORKS連携が有効化されていません。');
        }

        $timezone = $this->timezone();
        $start = $scheduledAt->copy()->setTimezone($timezone);
        $end = $start->copy()->addMinutes($this->eventDurationMinutes());

        $event = $this->makeEvent(
            sprintf('職場見学(%s)', $candidate->name),
            $start,
            $end,
            [
                'description' => $this->buildDescription($candidate),
                'timeZone' => $timezone,
            ]
        );

        $userId = $this->calendarUserId();

        $this->createEvent($userId, $event, $this->defaultCalendarId());
    }

    public function createEvent(string $userId, array $event, ?string $calendarId = null): array
    {
        $token = $this->accessToken();
        $effectiveCalendarId = $this->determineCalendarId($token, $userId, $calendarId);
        $url = $this->buildEventEndpoint($userId, $effectiveCalendarId);

        $eventPayload = $event;
        $offsetFallbackApplied = false;

        $payload = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'eventComponents' => [$eventPayload],
            ],
        ];

        $attempt = 0;
        $context = [
            'user_id' => $userId,
            'calendar_id' => $effectiveCalendarId,
            'calendar_name' => $this->calendarName(),
            'event_summary' => $event['summary'] ?? null,
            'event_start' => $event['start']['dateTime'] ?? null,
            'event_end' => $event['end']['dateTime'] ?? null,
        ];

        while (true) {
            $attempt++;

            try {
                $response = $this->client->post($url, $payload);
            } catch (RequestException $exception) {
                $errorDetails = $this->extractErrorDetails($exception->getResponse());

                if (!$offsetFallbackApplied && $this->shouldApplyStartTimeFallback($eventPayload, $errorDetails)) {
                    $offsetFallbackApplied = true;
                    $eventPayload = $this->withExplicitOffsetDates($eventPayload);
                    $payload['json']['eventComponents'] = [$eventPayload];

                    Log::warning('LINE WORKS start time fallback applied (offset appended).', array_filter(
                        array_merge($context, [
                            'attempt' => $attempt + 1,
                            'description' => $errorDetails['description'] ?? null,
                            'code' => $errorDetails['code'] ?? null,
                        ]),
                        static fn ($value) => $value !== null && $value !== ''
                    ));

                    continue;
                }

                Log::error('LINE WORKSカレンダーへのリクエストで例外が発生しました。', array_filter(
                    array_merge($context, [
                        'message' => $exception->getMessage(),
                        'status' => $errorDetails['status'] ?? null,
                        'body' => $errorDetails['body'] ?? null,
                        'code' => $errorDetails['code'] ?? null,
                        'description' => $errorDetails['description'] ?? null,
                        'attempt' => $attempt,
                    ]),
                    static fn ($value) => $value !== null && $value !== ''
                ));

                $message = $this->formatLineworksErrorMessage(
                    $errorDetails['description'] ?? null,
                    $errorDetails['code'] ?? null
                );

                throw $this->buildLineworksException($errorDetails['code'] ?? null, $message, $exception);
            } catch (GuzzleException $exception) {
                Log::error('LINE WORKSカレンダーへのリクエストで例外が発生しました。', array_merge($context, [
                    'message' => $exception->getMessage(),
                    'attempt' => $attempt,
                ]));

                throw new RuntimeException('LINE WORKSカレンダーへの登録に失敗しました。', 0, $exception);
            }

            $status = $response->getStatusCode();

            if ($status >= 300) {
                $errorDetails = $this->parseErrorBody((string) $response->getBody());

                if (!$offsetFallbackApplied && $this->shouldApplyStartTimeFallback($eventPayload, $errorDetails)) {
                    $offsetFallbackApplied = true;
                    $eventPayload = $this->withExplicitOffsetDates($eventPayload);
                    $payload['json']['eventComponents'] = [$eventPayload];

                    Log::warning('LINE WORKS start time fallback applied (offset appended).', array_filter(
                        array_merge($context, [
                            'attempt' => $attempt + 1,
                            'description' => $errorDetails['description'] ?? null,
                            'code' => $errorDetails['code'] ?? null,
                        ]),
                        static fn ($value) => $value !== null && $value !== ''
                    ));

                    continue;
                }

                Log::error('LINE WORKSカレンダーへの登録が失敗しました。', array_filter(
                    array_merge($context, [
                        'status' => $status,
                        'body' => $errorDetails['body'] ?? null,
                        'code' => $errorDetails['code'] ?? null,
                        'description' => $errorDetails['description'] ?? null,
                        'attempt' => $attempt,
                    ]),
                    static fn ($value) => $value !== null && $value !== ''
                ));

                $message = $this->formatLineworksErrorMessage(
                    $errorDetails['description'] ?? null,
                    $errorDetails['code'] ?? null
                );

                throw $this->buildLineworksException($errorDetails['code'] ?? null, $message, $response);
            }

            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            return is_array($decoded) ? $decoded : [];
        }
    }

    /**
     * @param array<string, mixed> $event
     * @param array{code?: string, description?: string} $errorDetails
     */
    private function shouldApplyStartTimeFallback(array $event, array $errorDetails): bool
    {
        $description = $errorDetails['description'] ?? '';

        if (!is_string($description) || $description === '') {
            return false;
        }

        if (!str_contains(mb_strtolower($description), 'start time not set')) {
            return false;
        }

        return isset($event['start']['dateTime'], $event['start']['timeZone'], $event['end']['dateTime'], $event['end']['timeZone']);
    }

    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function withExplicitOffsetDates(array $event): array
    {
        if (isset($event['start']['dateTime'], $event['start']['timeZone']) && is_string($event['start']['dateTime']) && is_string($event['start']['timeZone'])) {
            $event['start']['dateTime'] = $this->appendOffsetIfMissing($event['start']['dateTime'], $event['start']['timeZone']);
        }

        if (isset($event['end']['dateTime'], $event['end']['timeZone']) && is_string($event['end']['dateTime']) && is_string($event['end']['timeZone'])) {
            $event['end']['dateTime'] = $this->appendOffsetIfMissing($event['end']['dateTime'], $event['end']['timeZone']);
        }

        return $event;
    }

    private function appendOffsetIfMissing(string $dateTime, string $timeZone): string
    {
        if ($this->dateTimeHasExplicitOffset($dateTime)) {
            return $dateTime;
        }

        try {
            $carbon = Carbon::parse($dateTime, $timeZone);
        } catch (Throwable $exception) {
            Log::warning('Failed to append offset to LINE WORKS dateTime.', [
                'dateTime' => $dateTime,
                'timeZone' => $timeZone,
                'message' => $exception->getMessage(),
            ]);

            return $dateTime;
        }

        return $carbon->setTimezone($timeZone)->format('Y-m-d\TH:i:sP');
    }

    private function dateTimeHasExplicitOffset(string $value): bool
    {
        return (bool) preg_match('/([+-]\d{2}:\d{2}|Z)$/', $value);
    }

    public function makeEvent(string $summary, string|CarbonInterface $startDateTime, string|CarbonInterface $endDateTime, array $options = []): array
    {
        $timeZone = $options['timeZone'] ?? $this->timezone();

        $event = [
            'summary' => $summary,
            'start' => [
                'dateTime' => $this->normalizeEventDateTime($startDateTime, $timeZone),
                'timeZone' => $timeZone,
            ],
            'end' => [
                'dateTime' => $this->normalizeEventDateTime($endDateTime, $timeZone),
                'timeZone' => $timeZone,
            ],
        ];

        if (array_key_exists('description', $options)) {
            $event['description'] = $options['description'];
        }

        if (array_key_exists('location', $options)) {
            $event['location'] = $options['location'];
        }

        $extra = array_diff_key($options, array_flip(['description', 'location', 'timeZone']));

        return array_merge($event, $extra);
    }

    /**
     * @param string|CarbonInterface $value
     */
    private function normalizeEventDateTime(string|CarbonInterface $value, string $timeZone): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->copy()->setTimezone($timeZone)->format('Y-m-d\TH:i:sP');
        }

        try {
            $dateTime = Carbon::parse($value, $timeZone);
        } catch (Throwable $exception) {
            throw new RuntimeException('Invalid event dateTime: ' . $value, 0, $exception);
        }

    return $dateTime->setTimezone($timeZone)->format('Y-m-d\TH:i:sP');
    }

    public function accessToken(): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('LINE WORKS連携が有効化されていません。');
        }

        $cacheKey = 'lineworks_token_' . md5($this->configValue('client_id', ''));

        if ($cached = Cache::get($cacheKey)) {
            return (string) $cached;
        }

        $jwt = $this->buildClientAssertion();

        try {
            $response = $this->client->post($this->configValue('auth_url', ''), [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                    'client_id' => $this->configValue('client_id'),
                    'client_secret' => $this->configValue('client_secret'),
                    'scope' => $this->configValue('scope', 'calendar'),
                ],
            ]);
        } catch (GuzzleException $exception) {
            $context = [
                'message' => $exception->getMessage(),
            ];

            $errorDescription = null;

            if ($exception instanceof RequestException && $exception->hasResponse()) {
                $response = $exception->getResponse();
                $context['status'] = $response->getStatusCode();
                $body = (string) $response->getBody();
                $context['body'] = $body;

                $decodedError = json_decode($body, true);
                if (is_array($decodedError)) {
                    $errorDescription = $decodedError['error_description'] ?? null;
                    $context['error'] = $decodedError['error'] ?? null;
                }
            }

            Log::error('LINE WORKSアクセストークンの取得に失敗しました。', $context);

            $message = 'LINE WORKSアクセストークンの取得に失敗しました。';
            if (is_string($errorDescription) && $errorDescription !== '') {
                $message .= ' (' . $errorDescription . ')';
            }

            throw new RuntimeException($message, 0, $exception);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if ($status >= 300 || !is_array($decoded) || empty($decoded['access_token'])) {
            Log::error('LINE WORKSアクセストークンの取得レスポンスが不正です。', [
                'status' => $status,
                'body' => $body,
            ]);

            throw new RuntimeException('LINE WORKSアクセストークンの取得に失敗しました。');
        }

        $ttlSeconds = (int) ($decoded['expires_in'] ?? 3600);
        $ttlMinutes = max(1, min(50, (int) floor($ttlSeconds / 60)));
        Cache::put($cacheKey, $decoded['access_token'], now()->addMinutes($ttlMinutes));

        return $decoded['access_token'];
    }

    private function buildClientAssertion(): string
    {
        $now = time();
        $payload = [
            'iss' => $this->configValue('client_id'),
            'sub' => $this->configValue('service_account'),
            'aud' => $this->resolveAudience($this->configValue('auth_url', '')),
            'iat' => $now,
            'exp' => $now + 600,
            'jti' => (string) Str::uuid(),
            'scope' => $this->configValue('scope', 'calendar'),
        ];

        $privateKey = $this->loadPrivateKey();
        $algorithm = $this->configValue('jwt_alg', 'RS256');
        $keyId = $this->configValue('key_id');

        if (is_string($keyId) && $keyId !== '') {
            return JWT::encode($payload, $privateKey, $algorithm, $keyId);
        }

        return JWT::encode($payload, $privateKey, $algorithm);
    }

    private function loadPrivateKey(): string
    {
        if (!empty($this->config['private_key_pem'])) {
            return str_replace('\n', "\n", $this->config['private_key_pem']);
        }

        if (!empty($this->config['private_key_path'])) {
            $path = $this->resolvePrivateKeyPath((string) $this->config['private_key_path']);

            if (!is_readable($path)) {
                throw new RuntimeException('LINE WORKS private key file is not readable: ' . $path);
            }

            return (string) file_get_contents($path);
        }

        throw new RuntimeException('LINE WORKS private key not configured');
    }

    /**
     * @return array<string, mixed>
     */
    private function makeClientOptions(): array
    {
        $options = ['timeout' => 15];

        $verify = $this->resolveVerifyOption();

        $this->logVerifyDecision($verify);

        if ($verify !== null) {
            $options['verify'] = $verify;
        }

        return $options;
    }

    private function resolveVerifyOption(): bool|string|null
    {
        $verify = $this->configValue('verify_ssl', true);

        if ($this->isExplicitBooleanFalse($verify)) {
            return false;
        }

        $caBundle = $this->configValue('ca_bundle_path');

        if (!is_string($caBundle)) {
            return null;
        }

        $caBundle = trim($caBundle);

        if ($caBundle === '') {
            return null;
        }

        $path = $this->resolveCaBundlePath($caBundle);

        if (!is_readable($path)) {
            Log::error('LINE WORKS CA bundle file is not readable.', [
                'configured_path' => $caBundle,
                'resolved_path' => $path,
            ]);
            throw new RuntimeException('LINE WORKS CA bundle file is not readable: ' . $path);
        }

        return $path;
    }

    private function resolveCaBundlePath(string $path): string
    {
        return $this->resolvePrivateKeyPath($path);
    }

    private function logVerifyDecision(bool|string|null $verify): void
    {
        if ($verify === null) {
            Log::info('LINE WORKS HTTP client will use default CA store for SSL verification.');

            return;
        }

        if ($verify === false) {
            Log::warning('LINE WORKS HTTP client SSL verification is disabled by configuration.');

            return;
        }

        Log::info('LINE WORKS HTTP client will use custom CA bundle for SSL verification.', [
            'ca_bundle_path' => $verify,
        ]);
    }

    private function isExplicitBooleanFalse(mixed $value): bool
    {
        if ($value === false) {
            return true;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));

            return in_array($value, ['0', 'false', 'off', 'no'], true);
        }

        if (is_int($value)) {
            return $value === 0;
        }

        return false;
    }

    private function determineCalendarId(string $token, string $userId, ?string $calendarId): ?string
    {
        if (is_string($calendarId) && trim($calendarId) !== '') {
            return trim($calendarId);
        }

        $configuredId = $this->defaultCalendarId();
        if ($configuredId !== null) {
            return $configuredId;
        }

        $calendarName = $this->calendarName();
        if ($calendarName === null) {
            return null;
        }

        $calendars = $this->fetchCalendars($token, $userId);

        return $this->resolveCalendarIdByName($calendars, $calendarName, $this->preferredCalendarType());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCalendars(string $token, string $userId): array
    {
        $url = rtrim($this->configValue('api_base', ''), '/') . '/users/' . rawurlencode($userId) . '/calendars';

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (GuzzleException $exception) {
            Log::error('LINE WORKSカレンダー一覧の取得で例外が発生しました。', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('LINE WORKSカレンダー一覧の取得に失敗しました。', 0, $exception);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if ($status >= 300 || !is_array($decoded)) {
            Log::error('LINE WORKSカレンダー一覧の取得レスポンスが不正です。', [
                'user_id' => $userId,
                'status' => $status,
                'body' => $body,
            ]);

            throw new RuntimeException('LINE WORKSカレンダー一覧の取得に失敗しました。');
        }

        $calendars = $decoded['calendars'] ?? null;

        if (!is_array($calendars)) {
            Log::error('LINE WORKSカレンダー一覧にcalendarsキーが存在しません。', [
                'user_id' => $userId,
                'body' => $body,
            ]);

            throw new RuntimeException('LINE WORKSカレンダー一覧の取得に失敗しました。');
        }

        return $calendars;
    }

    /**
     * @param array<int, array<string, mixed>> $calendars
     */
    private function resolveCalendarIdByName(array $calendars, string $name, ?string $preferredType): string
    {
        $matches = array_values(array_filter($calendars, static function ($calendar) use ($name) {
            if (!is_array($calendar)) {
                return false;
            }

            $calendarName = $calendar['name'] ?? null;

            return is_string($calendarName) && mb_stripos($calendarName, $name) !== false;
        }));

        if ($preferredType !== null) {
            $matches = array_values(array_filter($matches, static function ($calendar) use ($preferredType) {
                $type = $calendar['type'] ?? null;

                return is_string($type) && strtolower($type) === $preferredType;
            }));

            if (empty($matches)) {
                throw new RuntimeException(sprintf('指定タイプ(%s)でカレンダー「%s」が見つかりませんでした。', $preferredType, $name));
            }
        }

        if (empty($matches)) {
            throw new RuntimeException(sprintf('カレンダー「%s」が見つかりませんでした。', $name));
        }

        $order = ['team', 'company', 'user'];
        if ($preferredType !== null) {
            $order = array_values(array_unique(array_merge([$preferredType], $order)));
        }

        usort($matches, static function (array $a, array $b) use ($order) {
            $defaultA = !empty($a['isDefault']);
            $defaultB = !empty($b['isDefault']);

            if ($defaultA !== $defaultB) {
                return $defaultA ? -1 : 1;
            }

            $typeA = strtolower((string) ($a['type'] ?? ''));
            $typeB = strtolower((string) ($b['type'] ?? ''));

            $indexA = array_search($typeA, $order, true);
            $indexB = array_search($typeB, $order, true);
            $indexA = $indexA === false ? PHP_INT_MAX : $indexA;
            $indexB = $indexB === false ? PHP_INT_MAX : $indexB;

            if ($indexA !== $indexB) {
                return $indexA <=> $indexB;
            }

            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        $candidate = $matches[0];
        $calendarId = $candidate['calendarId'] ?? null;

        if (!is_string($calendarId) || trim($calendarId) === '') {
            throw new RuntimeException('カレンダーIDを特定できませんでした。');
        }

        return trim($calendarId);
    }

    private function buildEventEndpoint(string $userId, ?string $calendarId): string
    {
        $base = rtrim($this->configValue('api_base', ''), '/');
        $encodedUser = rawurlencode($userId);

        if (is_string($calendarId) && $calendarId !== '') {
            return sprintf(
                '%s/users/%s/calendars/%s/events',
                $base,
                $encodedUser,
                rawurlencode($calendarId)
            );
        }

        return sprintf('%s/users/%s/calendar/events', $base, $encodedUser);
    }

    private function defaultCalendarId(): ?string
    {
        $calendarId = $this->configValue('calendar_id');

        if (!is_string($calendarId)) {
            return null;
        }

        $calendarId = trim($calendarId);

        return $calendarId !== '' ? $calendarId : null;
    }

    private function calendarName(): ?string
    {
        $name = $this->configValue('calendar_name');

        if (!is_string($name)) {
            return null;
        }

        $name = trim($name);

        return $name !== '' ? $name : null;
    }

    private function preferredCalendarType(): ?string
    {
        $type = $this->configValue('calendar_prefer_type');

        if (!is_string($type)) {
            return null;
        }

        $type = strtolower(trim($type));

        return in_array($type, ['team', 'company', 'user'], true) ? $type : null;
    }

    private function resolvePrivateKeyPath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return $path;
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private function resolveAudience(string $authUrl): string
    {
        $authUrl = trim($authUrl);

        if ($authUrl === '') {
            return $authUrl;
        }

        $parts = parse_url($authUrl);

        if ($parts === false) {
            return $authUrl;
        }

        if (!isset($parts['scheme'], $parts['host'])) {
            return $authUrl;
        }

        $audience = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $audience .= ':' . $parts['port'];
        }

        return $audience;
    }

    /**
     * @return array{status?: int, body?: string, code?: string, description?: string}
     */
    private function extractErrorDetails(?ResponseInterface $response): array
    {
        if ($response === null) {
            return [];
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $parsed = $this->parseErrorBody($body);
        $parsed['status'] = $status;

        return $parsed;
    }

    /**
     * @return array{body?: string, code?: string, description?: string}
     */
    private function parseErrorBody(string $body): array
    {
        $result = ['body' => $body];
        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $code = $decoded['code'] ?? null;
            $description = $decoded['description'] ?? $decoded['error_description'] ?? null;

            if (is_string($code) && $code !== '') {
                $result['code'] = $code;
            }

            if (is_string($description) && $description !== '') {
                $result['description'] = $description;
            }
        }

        return $result;
    }

    private function formatLineworksErrorMessage(?string $description, ?string $code): string
    {
        $base = 'LINE WORKSカレンダーへの登録に失敗しました。';

        if (is_string($description) && $description !== '') {
            return $base . '(' . $description . ')';
        }

        if (is_string($code) && $code !== '') {
            return $base . '(' . $code . ')';
        }

        return $base;
    }

    private function buildLineworksException(?string $code, string $message, mixed $previous): RuntimeException
    {
        if ($code === 'SERVICE_UNAVAILABLE') {
            return new LineworksServiceUnavailableException($message, 0, $previous instanceof Throwable ? $previous : null);
        }

        return new RuntimeException($message, 0, $previous instanceof Throwable ? $previous : null);
    }

    private function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return true;
        }

        if (preg_match('/^[A-Za-z]:\\\\|^[A-Za-z]:\//', $path) === 1) {
            return true;
        }

        return str_starts_with($path, '\\');
    }

    private function configValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    private function timezone(): string
    {
        return $this->configValue('default_tz', config('app.timezone', 'Asia/Tokyo'));
    }

    private function eventDurationMinutes(): int
    {
        return max(15, (int) $this->configValue('default_duration_minutes', 60));
    }

    private function calendarUserId(): string
    {
        $userId = $this->configValue('calendar_user_id', $this->configValue('service_account'));

        if (empty($userId)) {
            throw new RuntimeException('LINE WORKS calendar user is not configured.');
        }

        return $userId;
    }

    private function buildDescription(Candidate $candidate): string
    {
        $lines = [
            sprintf('候補者: %s', $candidate->name),
        ];

        if ($candidate->agency) {
            $lines[] = sprintf('派遣会社: %s', $candidate->agency->name);
        }

        if ($candidate->handler1) {
            $lines[] = sprintf('担当者1: %s', $candidate->handler1->name);
        }

        if ($candidate->handler2) {
            $lines[] = sprintf('担当者2: %s', $candidate->handler2->name);
        }

        if ($candidate->other_conditions) {
            $lines[] = '---';
            $lines[] = 'その他条件・メモ:';
            $lines[] = $candidate->other_conditions;
        }

        return implode("\n", $lines);
    }
}
