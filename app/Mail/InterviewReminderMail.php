<?php

namespace App\Mail;

use App\Models\Interview;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InterviewReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Interview $interview,
        public readonly string $slot
    ) {
    }

    public function build(): self
    {
        $timezone = config('reminder.timezone', config('app.timezone'));
        $scheduled = $this->interview->scheduled_at instanceof CarbonImmutable
            ? $this->interview->scheduled_at->setTimezone($timezone)
            : CarbonImmutable::parse($this->interview->scheduled_at, $timezone);

        $candidate = $this->interview->candidate;

        $subject = sprintf('[職場見学リマインド] %s さん %s', $candidate->name, $scheduled->format('Y/m/d H:i'));

        return $this->subject($subject)
            ->markdown('emails.interview_reminder', [
                'candidate' => $candidate,
                'agency' => $candidate->agency,
                'handlers' => $candidate->handlerCollection(),
                'owner' => $candidate->createdBy,
                'scheduledAt' => $scheduled,
                'place' => $this->interview->place,
                'memo' => $this->interview->memo,
                'slotLabel' => $this->slotLabel($this->slot),
                'slot' => $this->slot,
                'timezone' => $timezone,
                'isThirtyMinutesGloballyDisabled' => (bool) config('reminder.disable_30m', false),
                'isThirtyMinutesEnabledForInterview' => (bool) $this->interview->remind_30m_enabled,
            ]);
    }

    protected function slotLabel(string $slot): string
    {
        return match ($slot) {
            'prev_day' => '前日 9:00 リマインド',
            'one_hour' => '1時間前リマインド',
            'thirty_minutes' => '30分前リマインド',
            default => 'リマインド',
        };
    }
}
