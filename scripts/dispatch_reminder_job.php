<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

App\Jobs\SendInterviewReminderJob::dispatch()
    ->onConnection(config('queue.default'))
    ->onQueue('reminders');

echo "Job dispatched\n";
