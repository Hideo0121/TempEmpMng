<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'manager' => \App\Http\Middleware\EnsureUserIsManager::class,
        ]);
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->call(function () {
            \App\Jobs\SendInterviewReminderJob::dispatch()
                ->onConnection(config('queue.default'))
                ->onQueue('reminders');
        })->everyFiveMinutes()
            ->name('dispatch-interview-reminders');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
