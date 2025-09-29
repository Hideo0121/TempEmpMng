<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$interview = App\Models\Interview::with(['candidate.handler1', 'candidate.handler2', 'candidate.agency', 'candidate.createdBy'])
    ->first();

if (!$interview) {
    echo "No interviews found\n";
    exit(0);
}

$job = new App\Jobs\SendInterviewReminderJob();
$reflection = new ReflectionClass($job);
$method = $reflection->getMethod('buildToAddresses');
$method->setAccessible(true);

$recipients = $method->invoke($job, $interview);

echo "Recipients:\n";
print_r($recipients);
