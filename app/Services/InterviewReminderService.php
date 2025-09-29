<?php

namespace App\Services;

use App\Models\Interview;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class InterviewReminderService
{
    private const SLOTS = ['prev_day', 'one_hour', 'thirty_minutes'];

    /**
     * Iterate interviews scheduled for reminder slots and invoke the callback for each due slot.
     *
     * @param callable(Interview,string):void $callback
     */
    public function processDueSlots(CarbonImmutable $now, callable $callback): void
    {
        Interview::query()
            ->with(['candidate', 'candidate.agency', 'candidate.handler1', 'candidate.handler2', 'candidate.createdBy'])
            ->chunkById(100, function ($chunk) use ($callback, $now) {
                foreach ($chunk as $interview) {
                    foreach (self::SLOTS as $slot) {
                        if (!$this->shouldSendReminder($interview, $slot, $now)) {
                            continue;
                        }

                        $callback($interview, $slot);
                    }
                }
            });
    }

    protected function shouldSendReminder(Interview $interview, string $slot, CarbonImmutable $current): bool
    {
        if ($slot === 'thirty_minutes' && ($interview->remind_30m_enabled === false || config('reminder.disable_30m'))) {
            return false;
        }

        $flagColumn = match ($slot) {
            'prev_day' => 'remind_prev_day_sent',
            'one_hour' => 'remind_1h_sent',
            'thirty_minutes' => 'remind_30m_sent',
        };

        if ($interview->{$flagColumn}) {
            return false;
        }

        $scheduled = $this->scheduledAt($interview);

        $expectedTime = match ($slot) {
            'prev_day' => $scheduled->subDay()->setTime(9, 0),
            'one_hour' => $scheduled->subHour(),
            'thirty_minutes' => $scheduled->subMinutes(30),
        };

        return $current->betweenIncluded($expectedTime->subMinutes(2), $expectedTime->addMinutes(2));
    }

    protected function scheduledAt(Interview $interview): CarbonImmutable
    {
        $timezone = $this->timezone();
        $scheduled = $interview->scheduled_at;

        if ($scheduled instanceof CarbonInterface) {
            return CarbonImmutable::createFromInterface($scheduled)->setTimezone($timezone);
        }

        if ($scheduled === null) {
            return CarbonImmutable::now($timezone);
        }

        return CarbonImmutable::parse((string) $scheduled, $timezone);
    }

    protected function timezone(): string
    {
        return config('reminder.timezone', config('app.timezone'));
    }
}
