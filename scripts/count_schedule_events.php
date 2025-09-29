<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schedule = new Illuminate\Console\Scheduling\Schedule($app);
$kernelInstance = app(App\Console\Kernel::class);
$scheduleInvoker = \Closure::bind(function () use ($kernelInstance, $schedule) {
    $kernelInstance->schedule($schedule);
}, null, App\Console\Kernel::class);
$scheduleInvoker();

echo 'Event count: ' . count($schedule->events()) . PHP_EOL;
foreach ($schedule->events() as $event) {
    echo '- Description: ' . ($event->description ?? '(none)') . PHP_EOL;
    echo '  Expression: ' . $event->expression . PHP_EOL;
}
