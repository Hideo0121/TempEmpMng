<?php

namespace App\Jobs;

use App\Mail\InterviewReminderMail;
use App\Models\Interview;
use App\Models\Notification;
use Carbon\CarbonImmutable;
use App\Services\InterviewReminderService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInterviewReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Batchable;

    public function __construct()
    {
        $this->connection = config('queue.default');
        $this->queue = 'reminders';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $timezone = config('reminder.timezone', config('app.timezone'));
        $now = CarbonImmutable::now($timezone);
        app(InterviewReminderService::class)->processDueSlots($now, function (Interview $interview, string $slot) use ($now) {
            $this->queueReminder($interview, $slot, $now);
        });
    }

    protected function queueReminder(Interview $interview, string $slot, CarbonImmutable $queuedAt): void
    {
        $to = $this->buildToAddresses($interview);
        $cc = $this->buildCcAddresses($interview);

        $queueName = config('queue.notification_mail_queue', 'reminders');

        Log::info('Queueing interview reminder', [
            'interview_id' => $interview->id,
            'slot' => $slot,
            'to' => $to,
            'cc' => $cc,
            'queue' => $queueName,
        ]);

        $notification = Notification::create([
            'type' => 'interview_reminder',
            'target_id' => $interview->id,
            'to_addresses' => json_encode($to),
            'cc_addresses' => json_encode($cc),
            'subject' => $this->buildSubject($interview),
            'body' => '',
            'scheduled_for' => $queuedAt,
            'status' => 'pending',
        ]);

        if (empty($to)) {
            $notification->update([
                'status' => 'skipped',
                'error_message' => 'No recipient addresses available.',
            ]);

            Log::warning('Interview reminder skipped because no recipients were found.', [
                'interview_id' => $interview->id,
                'slot' => $slot,
            ]);

            return;
        }

        try {
            Mail::to($to)
                ->cc($cc)
                ->queue(
                    (new InterviewReminderMail($interview, $slot))
                        ->onQueue($queueName)
                );

            $notification->update([
                'status' => 'sent',
                'sent_at' => CarbonImmutable::now(config('reminder.timezone', config('app.timezone'))),
            ]);

            $this->markSent($interview, $slot);
        } catch (\Throwable $e) {
            Log::error('Interview reminder send failed', [
                'interview_id' => $interview->id,
                'slot' => $slot,
                'error' => $e->getMessage(),
            ]);

            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected function buildSubject(Interview $interview): string
    {
        $candidate = $interview->candidate;
        $timezone = config('reminder.timezone', config('app.timezone'));
        $scheduled = $interview->scheduled_at instanceof CarbonImmutable
            ? $interview->scheduled_at->setTimezone($timezone)
            : CarbonImmutable::parse($interview->scheduled_at, $timezone);

        return sprintf('[職場見学リマインド] %s さん %s', $candidate->name, $scheduled->format('Y/m/d H:i'));
    }

    protected function buildToAddresses(Interview $interview): array
    {
        $candidate = $interview->candidate;
        $ownerEmail = optional($candidate->createdBy)->email;

        $addresses = $candidate->handlerCollection()
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => trim($email))
            ->filter();

        $agencyEmail = optional($candidate->agency)->email;

        if ($agencyEmail) {
            $addresses->push(trim($agencyEmail));
        }

        if ($ownerEmail) {
            $trimmedOwner = trim($ownerEmail);

            $addresses = $addresses->reject(fn ($email) => strcasecmp($email, $trimmedOwner) === 0);
        }

        return $addresses
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function buildCcAddresses(Interview $interview): array
    {
        $cc = collect(explode(',', (string) config('reminder.cc_managers', '')))
            ->map(fn ($email) => trim($email))
            ->filter();

        $ownerEmail = optional($interview->candidate->createdBy)->email;

        if ($ownerEmail) {
            $trimmedOwner = trim($ownerEmail);

            $cc = $cc->reject(fn ($email) => strcasecmp($email, $trimmedOwner) === 0);
        }

        return $cc
            ->unique()
            ->values()
            ->all();
    }

    protected function markSent(Interview $interview, string $slot): void
    {
        if ($slot !== 'thirty_minutes') {
            return;
        }

        $interview->forceFill(['remind_30m_sent' => true])->save();
    }
}
