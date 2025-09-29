<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$interviews = \App\Models\Interview::query()
    ->with(['candidate.handler1', 'candidate.handler2', 'candidate.agency', 'candidate.createdBy'])
    ->orderByDesc('id')
    ->take(5)
    ->get(['id', 'candidate_id', 'scheduled_at', 'remind_prev_day_sent', 'remind_1h_sent', 'remind_30m_sent', 'remind_30m_enabled']);

echo "Interviews:\n";
echo $interviews->toJson(JSON_PRETTY_PRINT), PHP_EOL, PHP_EOL;

if ($interviews->isNotEmpty()) {
    $interview = $interviews->first();
    echo 'Raw scheduled_at: ' . $interview->getRawOriginal('scheduled_at') . PHP_EOL;
    if ($interview->scheduled_at instanceof \Carbon\CarbonInterface) {
        echo 'scheduled_at timezone: ' . $interview->scheduled_at->timezoneName . PHP_EOL;
    }
    echo 'PHP default timezone: ' . date_default_timezone_get() . PHP_EOL;
    $timezone = config('reminder.timezone', config('app.timezone'));
    $scheduledLocal = $interview->scheduled_at instanceof \Carbon\CarbonInterface
        ? \Carbon\CarbonImmutable::createFromInterface($interview->scheduled_at)->setTimezone($timezone)
        : \Carbon\CarbonImmutable::parse((string) $interview->scheduled_at, $timezone);

    $slotTimes = [
        'prev_day' => $scheduledLocal->subDay()->setTime(9, 0),
        'one_hour' => $scheduledLocal->subHour(),
        'thirty_minutes' => $scheduledLocal->subMinutes(30),
    ];

    echo "Slot windows (local timezone {$timezone}):\n";
    foreach ($slotTimes as $slot => $time) {
        echo sprintf(
            "- %s: target %s (window %s ~ %s)\n",
            $slot,
            $time->format('Y-m-d H:i:s'),
            $time->copy()->subMinutes(2)->format('Y-m-d H:i:s'),
            $time->copy()->addMinutes(2)->format('Y-m-d H:i:s')
        );
    }

    echo PHP_EOL;
}

$notifications = \App\Models\Notification::query()
    ->orderByDesc('id')
    ->take(5)
    ->get(['id', 'type', 'target_id', 'scheduled_for', 'sent_at', 'status', 'to_addresses', 'cc_addresses']);

echo "Notifications:\n";
echo $notifications->toJson(JSON_PRETTY_PRINT), PHP_EOL, PHP_EOL;

$jobs = DB::table('jobs')->orderByDesc('id')->limit(5)->get();

echo "Jobs:\n";
echo $jobs->toJson(JSON_PRETTY_PRINT), PHP_EOL;
