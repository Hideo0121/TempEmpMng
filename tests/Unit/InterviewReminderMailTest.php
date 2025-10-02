<?php

namespace Tests\Unit;

use App\Mail\InterviewReminderMail;
use App\Models\Agency;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\JobCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class InterviewReminderMailTest extends TestCase
{
    public function test_mail_builds_with_expected_subject_and_body(): void
    {
        Config::set('reminder.timezone', 'Asia/Tokyo');

        $candidate = Candidate::make([
            'name' => '山田 太郎',
            'name_kana' => 'ヤマダ タロウ',
        ]);

        $candidate->setRelation('agency', Agency::make(['name' => 'テスト派遣会社']));
        $candidate->setRelation('handler1', User::make(['name' => '担当者A', 'email' => 'a@example.com']));
        $candidate->setRelation('handler2', User::make(['name' => '担当者B', 'email' => 'b@example.com']));
        $candidate->setRelation('createdBy', User::make(['name' => '登録者', 'email' => 'owner@example.com']));
        $candidate->setRelation('wishJob1', JobCategory::make(['name' => '第一希望職種']));
        $candidate->setRelation('wishJob2', JobCategory::make(['name' => '第二希望職種']));

        $interview = Interview::make([
            'scheduled_at' => CarbonImmutable::parse('2025-10-01 10:00:00', 'Asia/Tokyo'),
            'place' => '本社オフィス',
            'memo' => '入館証を忘れずに。',
            'remind_30m_enabled' => true,
        ]);

        $interview->setRelation('candidate', $candidate);

        $mail = new InterviewReminderMail($interview, 'thirty_minutes');
        $built = $mail->build();

        $this->assertStringContainsString('[職場見学リマインド] 山田 太郎 さん 2025/10/01 10:00', $built->subject);

        $rendered = $built->render();

        $this->assertStringContainsString('担当者A', $rendered);
        $this->assertStringContainsString('30分前リマインド', $rendered);
        $this->assertStringContainsString('希望職種①: 第一希望職種', $rendered);
        $this->assertStringContainsString('希望職種②: 第二希望職種', $rendered);
    }
}
