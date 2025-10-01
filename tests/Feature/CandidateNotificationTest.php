<?php

namespace Tests\Feature;

use App\Mail\CandidateAssignmentMail;
use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CandidateNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();
    }

    private function seedLookups(): array
    {
        $agency = Agency::create([
            'name' => '通知テスト派遣会社',
            'is_active' => true,
        ]);

        $status = CandidateStatus::create([
            'code' => 'ENTRY',
            'label' => 'エントリー',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);

        $job = JobCategory::create([
            'name' => '一般事務',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        CandidateStatus::refreshEmployedCache();

        return compact('agency', 'status', 'job');
    }

    public function test_store_sends_notification_to_handlers_when_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $handler = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'job' => $job] = $this->seedLookups();

        $payload = [
            'name' => '通知テスト候補者',
            'name_kana' => 'ツウチテストコウホシャ',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $job->id,
            'wish_job2' => null,
            'wish_job3' => null,
            'handler1' => $handler->id,
            'handler2' => null,
            'status' => $status->code,
            'status_changed_on' => Carbon::today()->toDateString(),
            'remind_30m_enabled' => '1',
            'notify_handlers' => '1',
        ];

        $response = $this->actingAs($user)->post(route('candidates.store'), $payload);

        $response->assertRedirect(route('dashboard'));

    $expectedQueue = config('queue.notification_mail_queue', 'reminders');

    Mail::assertQueued(CandidateAssignmentMail::class, function (CandidateAssignmentMail $mail) use ($handler, $expectedQueue) {
            return $mail->hasTo($handler->email)
                && $mail->recipient->is($handler)
        && $mail->isUpdate === false
        && $mail->queue === $expectedQueue;
        });
    }

    public function test_update_skips_notification_when_not_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $handler = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'job' => $job] = $this->seedLookups();

        $candidate = Candidate::create([
            'name' => '既存候補者',
            'name_kana' => 'キゾンコウホシャ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $handler->id,
            'status_code' => $status->code,
            'status_changed_on' => Carbon::today(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $payload = [
            'name' => '更新後候補者',
            'name_kana' => 'コウシンゴコウホシャ',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $job->id,
            'wish_job2' => null,
            'wish_job3' => null,
            'handler1' => $handler->id,
            'handler2' => null,
            'status' => $status->code,
            'status_changed_on' => Carbon::today()->toDateString(),
            'remind_30m_enabled' => '1',
            'notify_handlers' => '0',
        ];

        $response = $this->actingAs($user)->put(route('candidates.update', $candidate), $payload);

    $response->assertRedirect(route('candidates.index'));

        Mail::assertNotQueued(CandidateAssignmentMail::class);
    }
}
