<?php

namespace Tests\Feature;

use App\Jobs\SendInterviewReminderJob;
use App\Mail\InterviewReminderMail;
use App\Models\Agency;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Notification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendInterviewReminderJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('reminder.timezone', 'Asia/Tokyo');
        Config::set('reminder.disable_30m', false);
        Config::set('reminder.cc_managers', 'manager@example.com');
    }

    public function test_job_sends_one_hour_reminder_and_updates_flags(): void
    {
        Mail::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-10-01 09:00:00', 'Asia/Tokyo'));

        try {
            $this->seedBaselineData();

            $agency = Agency::create([
                'name' => 'テスト派遣会社',
                'email' => 'agency@example.com',
            ]);

            $handler = User::factory()->create([
                'role' => 'staff',
                'email' => 'handler@example.com',
            ]);

            $owner = User::factory()->create([
                'role' => 'manager',
                'email' => 'owner@example.com',
            ]);

            $candidate = Candidate::create([
                'name' => '山田 太郎',
                'name_kana' => 'ヤマダ タロウ',
                'agency_id' => $agency->id,
                'introduced_on' => '2025-09-20',
                'status_code' => 'visit_pending',
                'handler1_user_id' => $handler->id,
                'created_by' => $owner->id,
            ]);

            $interview = Interview::create([
                'candidate_id' => $candidate->id,
                'scheduled_at' => '2025-10-01 10:00:00',
                'place' => '本社ビル',
                'memo' => '入館手続きに10分必要です。',
            ]);

            (new SendInterviewReminderJob())->handle();

            $notification = Notification::first();

            $this->assertNotNull($notification);
            $this->assertSame('sent', $notification->status);
            $this->assertSame('interview_reminder', $notification->type);
            $this->assertSame($interview->id, $notification->target_id);

            Mail::assertQueued(InterviewReminderMail::class, function (InterviewReminderMail $mail) use ($interview) {
                return $mail->interview->is($interview) && $mail->slot === 'one_hour';
            });

            $this->assertTrue($interview->fresh()->remind_1h_sent);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_job_skips_when_no_recipients_available(): void
    {
        Mail::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-10-01 09:00:00', 'Asia/Tokyo'));

        try {
            $this->seedBaselineData();

            $candidate = Candidate::create([
                'name' => '受信者なし',
                'name_kana' => 'ジュシンシャ ナシ',
                'agency_id' => Agency::create(['name' => '派遣先'])->id,
                'introduced_on' => '2025-09-20',
                'status_code' => 'visit_pending',
            ]);

            $interview = Interview::create([
                'candidate_id' => $candidate->id,
                'scheduled_at' => '2025-10-01 10:00:00',
            ]);

            (new SendInterviewReminderJob())->handle();

            $notification = Notification::first();

            $this->assertNotNull($notification);
            $this->assertSame('skipped', $notification->status);
            $this->assertSame('No recipient addresses available.', $notification->error_message);

            Mail::assertNothingQueued();

            $this->assertFalse($interview->fresh()->remind_1h_sent);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function seedBaselineData(): void
    {
        \DB::table('candidate_statuses')->insert([
            'code' => 'visit_pending',
            'label' => '職場見学待ち',
            'color_code' => '#0044cc',
            'sort_order' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
