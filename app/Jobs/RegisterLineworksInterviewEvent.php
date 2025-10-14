<?php

namespace App\Jobs;

use App\Exceptions\LineworksServiceUnavailableException;
use App\Models\Candidate;
use App\Services\LineworksCalendarService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegisterLineworksInterviewEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 120, 300, 600, 1200];

    public function __construct(private readonly int $candidateId)
    {
        $this->queue = 'reminders';
        $this->connection = config('queue.default');
    }

    public function handle(LineworksCalendarService $lineworks): void
    {
        $candidate = Candidate::with(['agency', 'handler1', 'handler2', 'confirmedInterview'])
            ->find($this->candidateId);

        if ($candidate === null) {
            Log::warning('LINE WORKS再登録ジョブ: 候補者が見つかりません。', [
                'candidate_id' => $this->candidateId,
            ]);

            return;
        }

        $confirmedAt = optional($candidate->confirmedInterview)->scheduled_at;

        if ($confirmedAt === null) {
            Log::warning('LINE WORKS再登録ジョブ: 見学確定日時が未設定です。', [
                'candidate_id' => $candidate->getKey(),
            ]);

            return;
        }

        $scheduled = $confirmedAt instanceof Carbon ? $confirmedAt : Carbon::parse((string) $confirmedAt);

        $lineworks->createInterviewEvent($candidate, $scheduled);

        Log::info('LINE WORKSカレンダー再登録ジョブが完了しました。', [
            'candidate_id' => $candidate->getKey(),
            'scheduled_at' => $scheduled->toIso8601String(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $severity = $exception instanceof LineworksServiceUnavailableException ? 'warning' : 'error';

        Log::{
            $severity
        }('LINE WORKSカレンダー再登録ジョブが失敗しました。', [
            'candidate_id' => $this->candidateId,
            'message' => $exception->getMessage(),
        ]);
    }
}
