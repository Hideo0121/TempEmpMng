<?php

namespace Tests\Unit;

use App\Exceptions\LineworksServiceUnavailableException;
use App\Models\Candidate;
use App\Services\LineworksCalendarService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Tests\TestCase;

class LineworksCalendarServiceTest extends TestCase
{
    public function test_create_event_sends_expected_payload(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $history = [];
        $mockHandler = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['eventId' => 'abc'])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $response = $service->createEvent('user-123', $event);

        $this->assertSame(['eventId' => 'abc'], $response);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://lineworks.test/v1/users/user-123/calendar/events', (string) $request->getUri());
        $this->assertSame(['Bearer token-abc'], $request->getHeader('Authorization'));

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertEquals(['eventComponents' => [$event]], $payload);
    }

    public function test_create_event_uses_configured_calendar_id(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_id' => 'c_abcdef',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $history = [];
        $mockHandler = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['eventId' => 'abc'])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $service->createEvent('user-123', $event);

        $request = $history[0]['request'];
        $this->assertSame(
            'https://lineworks.test/v1/users/user-123/calendars/c_abcdef/events',
            (string) $request->getUri()
        );
    }

    public function test_create_event_resolves_calendar_id_by_name(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_name' => '営業',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $history = [];
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'calendars' => [
                    ['calendarId' => 'c_team', 'name' => '営業チーム', 'type' => 'team', 'isDefault' => false],
                    ['calendarId' => 'c_company', 'name' => '会社営業スケジュール', 'type' => 'company', 'isDefault' => true],
                ],
            ])),
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['eventId' => 'abc'])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $service->createEvent('user-123', $event);

        $this->assertCount(2, $history);
        $this->assertSame('GET', $history[0]['request']->getMethod());
        $this->assertSame('https://lineworks.test/v1/users/user-123/calendars', (string) $history[0]['request']->getUri());
        $this->assertSame(
            'https://lineworks.test/v1/users/user-123/calendars/c_company/events',
            (string) $history[1]['request']->getUri()
        );
    }

    public function test_create_event_prefers_specified_calendar_type(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_name' => '営業',
            'calendar_prefer_type' => 'team',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $history = [];
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'calendars' => [
                    ['calendarId' => 'c_company', 'name' => '営業部', 'type' => 'company', 'isDefault' => true],
                    ['calendarId' => 'c_team1', 'name' => '営業チームA', 'type' => 'team', 'isDefault' => false],
                    ['calendarId' => 'c_team2', 'name' => '営業チームB', 'type' => 'team', 'isDefault' => true],
                ],
            ])),
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['eventId' => 'abc'])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $service->createEvent('user-123', $event);

        $this->assertCount(2, $history);
        $this->assertSame(
            'https://lineworks.test/v1/users/user-123/calendars/c_team2/events',
            (string) $history[1]['request']->getUri()
        );
    }

    public function test_create_event_throws_when_calendar_name_not_found(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_name' => '営業',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'calendars' => [
                    ['calendarId' => 'c_other', 'name' => '総務部', 'type' => 'team', 'isDefault' => false],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('カレンダー「営業」が見つかりませんでした。');

        $service->createEvent('user-123', $event);
    }

    public function test_load_private_key_accepts_absolute_path(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'lw-key-');
        file_put_contents($tempFile, 'absolute-key');

        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_path' => $tempFile,
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $service = new LineworksCalendarService(new Client());

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('loadPrivateKey');
        $method->setAccessible(true);

        $this->assertSame('absolute-key', $method->invoke($service));

        @unlink($tempFile);
    }

    public function test_resolve_audience_uses_auth_origin(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.worksmobile.com/oauth2/v2.0/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $service = new LineworksCalendarService(new Client());

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('resolveAudience');
        $method->setAccessible(true);

        $this->assertSame(
            'https://auth.worksmobile.com',
            $method->invoke($service, 'https://auth.worksmobile.com/oauth2/v2.0/token')
        );
        $this->assertSame(
            'https://auth.worksmobile.com:8443',
            $method->invoke($service, 'https://auth.worksmobile.com:8443/oauth2/v2.0/token')
        );
        $this->assertSame(
            'custom-scheme://example',
            $method->invoke($service, 'custom-scheme://example/path')
        );
        $this->assertSame(
            '',
            $method->invoke($service, '')
        );
    }

    public function test_create_event_retries_on_service_unavailable(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_id' => 'c_abcdef',
            'retry_attempts' => 3,
            'retry_delay_ms' => 0,
        ]);

        $history = [];
        $mockHandler = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'code' => 'SERVICE_UNAVAILABLE',
                'description' => 'Service failure',
            ])),
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['eventId' => 'xyz'])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $response = $service->createEvent('user-123', $event);

        $this->assertSame(['eventId' => 'xyz'], $response);
        $this->assertCount(2, $history);
    }

    public function test_create_event_applies_offset_fallback_on_start_time_error(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_id' => 'c_abcdef',
            'retry_attempts' => 3,
            'retry_delay_ms' => 0,
        ]);

        $history = [];
        $mockHandler = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'code' => 'INVALID_PARAMETER',
                'description' => 'Start time not set',
            ])),
            new Response(201, ['Content-Type' => 'application/json'], json_encode(['eventId' => 'fallback'])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00',
            '2024-01-01T11:00:00'
        );

        $response = $service->createEvent('user-123', $event);

        $this->assertSame(['eventId' => 'fallback'], $response);
        $this->assertCount(2, $history);

        $request = $history[1]['request'];
        $payload = json_decode((string) $request->getBody(), true);
        $start = $payload['eventComponents'][0]['start']['dateTime'] ?? null;
        $end = $payload['eventComponents'][0]['end']['dateTime'] ?? null;

        $this->assertNotNull($start);
        $this->assertNotNull($end);
        $this->assertStringEndsWith('+09:00', $start);
        $this->assertStringEndsWith('+09:00', $end);
    }

    public function test_create_event_throws_with_error_description(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_id' => 'c_abcdef',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $mockHandler = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'code' => 'INVALID_PARAMETER',
                'description' => 'Invalid date range',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LINE WORKSカレンダーへの登録に失敗しました。(Invalid date range)');

        $service->createEvent('user-123', $event);
    }

    public function test_create_event_throws_service_unavailable_exception_after_retries(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_id' => 'c_abcdef',
            'retry_attempts' => 2,
            'retry_delay_ms' => 0,
        ]);

        $mockHandler = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'code' => 'SERVICE_UNAVAILABLE',
                'description' => 'Service failure',
            ])),
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'code' => 'SERVICE_UNAVAILABLE',
                'description' => 'Service failure',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);

        $client = new Client(['handler' => $handlerStack]);

        $service = new class($client) extends LineworksCalendarService {
            public function __construct(Client $client)
            {
                parent::__construct($client);
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }
        };

        $event = $service->makeEvent(
            'テストイベント',
            '2024-01-01T10:00:00+09:00',
            '2024-01-01T11:00:00+09:00'
        );

        $this->expectException(LineworksServiceUnavailableException::class);
        $this->expectExceptionMessage('LINE WORKSカレンダーへの登録に失敗しました。(Service failure)');

        $service->createEvent('user-123', $event);
    }

    public function test_create_interview_event_uses_expected_summary_format(): void
    {
        config()->set('services.lineworks', [
            'enabled' => true,
            'auth_url' => 'https://auth.example.com/oauth/token',
            'api_base' => 'https://lineworks.test/v1',
            'client_id' => 'client-123',
            'client_secret' => 'secret-456',
            'service_account' => 'service-account@test',
            'private_key_pem' => 'dummy-key',
            'scope' => 'calendar',
            'default_tz' => 'Asia/Tokyo',
            'calendar_id' => 'c_abcdef',
            'retry_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);

        $collector = new class extends LineworksCalendarService {
            public array $captured = [];

            public function __construct()
            {
                parent::__construct(new Client());
            }

            public function accessToken(): string
            {
                return 'token-abc';
            }

            public function createEvent(string $userId, array $event, ?string $calendarId = null): array
            {
                $this->captured = [
                    'userId' => $userId,
                    'event' => $event,
                    'calendarId' => $calendarId,
                ];

                return ['ok' => true];
            }
        };

        $candidate = new Candidate(['name' => '山田 太郎']);
        $scheduledAt = Carbon::parse('2025-01-15 10:00:00', 'Asia/Tokyo');

        $collector->createInterviewEvent($candidate, $scheduledAt);

        $this->assertSame('職場見学(山田 太郎)', $collector->captured['event']['summary']);
    }
}
