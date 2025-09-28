<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserTestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly User $user)
    {
    }

    public function build(): self
    {
        $appName = config('app.name');

        return $this
            ->subject("【送信テスト】{$appName} 通知メール接続確認")
            ->markdown('emails.master.user_test', [
                'user' => $this->user,
                'appName' => $appName,
                'env' => config('app.env'),
                'now' => now(),
            ]);
    }
}
