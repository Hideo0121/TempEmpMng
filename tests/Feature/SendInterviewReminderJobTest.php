<?php

namespace Tests\Feature;

use App\Jobs\SendInterviewReminderJob;
use App\Mail\InterviewReminderMail;
use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
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

    public function test_job_sends_thirty_minute_reminder_to_handlers(): void
    {
        Mail::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-10-01 09:30:00', 'Asia/Tokyo'));

        try {
            $this->seedBaselineData();

            $agency = Agency::create([
                'name' => 'テスト派遣会社',
                'email' => 'agency@example.com',
            ]);

            $handler1 = User::factory()->create([
                'role' => 'staff',
                'email' => 'handler1@example.com',
            ]);

            $handler2 = User::factory()->create([
                'role' => 'staff',
                'email' => 'handler2@example.com',
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
                'status_code' => CandidateStatus::CODE_VISIT_PENDING,
                'handler1_user_id' => $handler1->id,
                'handler2_user_id' => $handler2->id,
                'created_by' => $owner->id,
            ]);

            $interview = Interview::create([
                'candidate_id' => $candidate->id,
                'scheduled_at' => '2025-10-01 10:00:00',
                'place' => '本社ビル',
                'memo' => '入館手続きに10分必要です。',
                'remind_30m_enabled' => true,
            ]);

            (new SendInterviewReminderJob())->handle();

            $notification = Notification::first();

            $this->assertNotNull($notification);
            $this->assertSame('sent', $notification->status);
            $this->assertSame('interview_reminder', $notification->type);
            $this->assertSame($interview->id, $notification->target_id);

            $this->assertEqualsCanonicalizing(
                ['handler1@example.com', 'handler2@example.com', 'agency@example.com'],
                json_decode($notification->to_addresses, true)
            );

            $this->assertSame(['manager@example.com'], json_decode($notification->cc_addresses, true));

            $expectedQueue = config('queue.notification_mail_queue', 'reminders');

            Mail::assertQueued(InterviewReminderMail::class, function (InterviewReminderMail $mail) use ($interview, $expectedQueue) {
                return $mail->interview->is($interview)
                    && $mail->slot === 'thirty_minutes'
                    && $mail->queue === $expectedQueue;
            });

            $this->assertTrue($interview->fresh()->remind_30m_sent);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_owner_email_is_not_included_even_if_listed_in_configuration(): void
    {
        Mail::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-10-01 09:30:00', 'Asia/Tokyo'));

        try {
            $this->seedBaselineData();

            Config::set('reminder.cc_managers', 'manager@example.com, owner@example.com');

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
                'name' => '高橋 次郎',
                'name_kana' => 'タカハシ ジロウ',
                'agency_id' => $agency->id,
                'introduced_on' => '2025-09-20',
                'status_code' => CandidateStatus::CODE_VISIT_PENDING,
                'handler1_user_id' => $handler->id,
                'created_by' => $owner->id,
            ]);

            $interview = Interview::create([
                'candidate_id' => $candidate->id,
                'scheduled_at' => '2025-10-01 10:00:00',
                'remind_30m_enabled' => true,
            ]);

            (new SendInterviewReminderJob())->handle();

            $notification = Notification::first();

            $this->assertEqualsCanonicalizing(
                ['handler@example.com', 'agency@example.com'],
                json_decode($notification->to_addresses, true)
            );

            $this->assertSame(['manager@example.com'], json_decode($notification->cc_addresses, true));

            Mail::assertQueued(InterviewReminderMail::class, function (InterviewReminderMail $mail) use ($owner) {
                return ! $mail->hasTo($owner->email)
                    && ! $mail->hasCc($owner->email);
            });
        } finally {
            CarbonImmutable::setTestNow();
            Config::set('reminder.cc_managers', 'manager@example.com');
        }
    }

    public function test_job_skips_thirty_minute_reminder_when_disabled_per_interview(): void
    {
        Mail::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-10-01 09:30:00', 'Asia/Tokyo'));

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
                'name' => '佐藤 花子',
                'name_kana' => 'サトウ ハナコ',
                'agency_id' => $agency->id,
                'introduced_on' => '2025-09-20',
                'status_code' => CandidateStatus::CODE_VISIT_PENDING,
                'handler1_user_id' => $handler->id,
                'created_by' => $owner->id,
            ]);

            $interview = Interview::create([
                'candidate_id' => $candidate->id,
                'scheduled_at' => '2025-10-01 10:00:00',
                'place' => '第2会議室',
                'memo' => '入館カードは受付で受け取る',
                'remind_30m_enabled' => false,
            ]);

            (new SendInterviewReminderJob())->handle();

            $this->assertSame(0, Notification::count());
            Mail::assertNothingQueued();
            $this->assertFalse($interview->fresh()->remind_30m_sent);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_job_skips_when_no_recipients_available(): void
    {
        Mail::fake();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-10-01 09:30:00', 'Asia/Tokyo'));

        try {
            $this->seedBaselineData();

            $candidate = Candidate::create([
                'name' => '受信者なし',
                'name_kana' => 'ジュシンシャ ナシ',
                'agency_id' => Agency::create(['name' => '派遣先'])->id,
                'introduced_on' => '2025-09-20',
                'status_code' => CandidateStatus::CODE_VISIT_PENDING,
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

            $this->assertFalse($interview->fresh()->remind_30m_sent);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function seedBaselineData(): void
    {
        \DB::table('candidate_statuses')->insert([
                'code' => CandidateStatus::CODE_VISIT_PENDING,
                'label' => '職場見学待',
                'color_code' => '#0044cc',
                'sort_order' => 10,
                'is_active' => true,
                'is_employed_state' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
