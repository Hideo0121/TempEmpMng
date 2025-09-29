<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$interviewId = (int) ($argv[1] ?? 0);
$newTime = $argv[2] ?? null;

if ($interviewId === 0 || $newTime === null) {
    fwrite(STDERR, "Usage: php scripts/set_interview_time.php <id> <Y-m-d H:i:s>\n");
    exit(1);
}

$interview = \App\Models\Interview::find($interviewId);

if (! $interview) {
    fwrite(STDERR, "Interview not found\n");
    exit(1);
}

$interview->forceFill([
    'scheduled_at' => $newTime,
    'remind_prev_day_sent' => false,
    'remind_1h_sent' => false,
    'remind_30m_sent' => false,
])->save();

fwrite(STDOUT, "Interview {$interviewId} updated to {$newTime}\n");
