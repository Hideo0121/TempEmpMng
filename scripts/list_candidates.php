<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$candidates = App\Models\Candidate::with(['handler1', 'handler2', 'agency', 'createdBy'])
    ->orderBy('id')
    ->get()
    ->map(function ($candidate) {
        return [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'handler1' => optional($candidate->handler1)->email,
            'handler2' => optional($candidate->handler2)->email,
            'agency_email' => optional($candidate->agency)->email,
            'created_by' => optional($candidate->createdBy)->email,
        ];
    });

echo $candidates->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
