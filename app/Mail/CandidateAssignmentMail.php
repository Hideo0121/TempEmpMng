<?php

namespace App\Mail;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CandidateAssignmentMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public User $recipient,
        public bool $isUpdate,
        public ?User $triggeredBy = null,
    ) {
    }

    public function build(): self
    {
        $this->candidate->loadMissing([
            'agency',
            'status',
            'wishJob1',
            'wishJob2',
            'wishJob3',
        ]);

        $actionLabel = $this->isUpdate ? '更新' : '登録';
        $subject = sprintf('[紹介者通知] %s さんの情報が%sされました', $this->candidate->name, $actionLabel);

        return $this->subject($subject)
            ->markdown('emails.candidate_assignment', [
                'candidate' => $this->candidate,
                'recipient' => $this->recipient,
                'isUpdate' => $this->isUpdate,
                'triggeredBy' => $this->triggeredBy,
                'candidateUrl' => route('candidates.show', $this->candidate),
                'actionLabel' => $actionLabel,
            ]);
    }
}
