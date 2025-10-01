<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') === 'sqlite') {
            $databasePath = config('database.connections.sqlite.database');

            if (is_string($databasePath) && $databasePath !== ':memory:') {
                $databasePath = str_starts_with($databasePath, 'file:')
                    ? substr($databasePath, 5)
                    : $databasePath;

                $directory = dirname($databasePath);

                if (! is_dir($directory)) {
                    @mkdir($directory, 0755, true);
                }

                $legacyPath = database_path('database.sqlite');

                if (! file_exists($databasePath) && file_exists($legacyPath)) {
                    @copy($legacyPath, $databasePath);
                }

                if (! file_exists($databasePath)) {
                    @touch($databasePath);
                }
            }
        }

        $globalBcc = trim((string) config('mail.always_bcc'));

        if ($globalBcc !== '') {
            Event::listen(MessageSending::class, static function (MessageSending $event) use ($globalBcc) {
                $message = $event->message;
                $existingBcc = $message->getBcc();

                if ($existingBcc) {
                    foreach ($existingBcc as $address) {
                        if (strcasecmp($address->getAddress(), $globalBcc) === 0) {
                            return;
                        }
                    }
                }

                $message->addBcc($globalBcc);
            });
        }
    }
}
