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

        $employedStatus = CandidateStatus::create([
            'code' => 'EMPLOYED',
            'label' => '就業決定',
            'sort_order' => 2,
            'is_active' => true,
            'is_employed_state' => true,
        ]);

        $job = JobCategory::create([
            'name' => '一般事務',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        CandidateStatus::refreshEmployedCache();

        return compact('agency', 'status', 'employedStatus', 'job');
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

    public function test_store_rejects_duplicate_wish_jobs(): void
    {
        $user = User::factory()->create();
        $handler = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'job' => $job] = $this->seedLookups();

        $payload = [
            'name' => '重複検証候補者',
            'name_kana' => 'ジュウフクケンショウコウホシャ',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $job->id,
            'wish_job2' => $job->id,
            'wish_job3' => null,
            'handler1' => $handler->id,
            'handler2' => null,
            'status' => $status->code,
            'status_changed_on' => Carbon::today()->toDateString(),
            'remind_30m_enabled' => '1',
        ];

        $response = $this->actingAs($user)->post(route('candidates.store'), $payload);

        $response->assertSessionHasErrors(['wish_job1' => '希望職種は重複しないよう選択してください。']);

        $this->assertSame(0, Candidate::count());
    }

    public function test_store_redirects_to_celebration_when_employed(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $handler = User::factory()->create();
        ['agency' => $agency, 'employedStatus' => $employedStatus, 'job' => $job] = $this->seedLookups();

        $payload = [
            'name' => 'お祝い候補者',
            'name_kana' => 'オイワイコウホシャ',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $job->id,
            'wish_job2' => null,
            'wish_job3' => null,
            'handler1' => $handler->id,
            'handler2' => null,
            'status' => $employedStatus->code,
            'decided_job' => $job->id,
            'status_changed_on' => Carbon::today()->toDateString(),
            'remind_30m_enabled' => '1',
        ];

        $response = $this->actingAs($user)->post(route('candidates.store'), $payload);

        $candidate = Candidate::first();

        $response->assertRedirect(route('candidates.celebrate'));
        $this->assertSame('候補者を登録しました。', session('status'));

        $response->assertSessionHas('celebration.payload', function ($payload) use ($candidate) {
            return is_array($payload)
                && ($payload['candidate_name'] ?? null) === $candidate->name
                && ($payload['status_label'] ?? null) === '就業決定'
                && ($payload['redirect_url'] ?? null) === route('dashboard')
                && ($payload['delay_seconds'] ?? null) === 5;
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

    public function test_update_redirects_to_celebration_when_status_changes_to_employed(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $handler = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'employedStatus' => $employedStatus, 'job' => $job] = $this->seedLookups();

        $candidate = Candidate::create([
            'name' => '就業前候補者',
            'name_kana' => 'シュウギョウマエコウホシャ',
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
            'name' => '就業決定候補者',
            'name_kana' => 'シュウギョウケッテイコウホシャ',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $job->id,
            'wish_job2' => null,
            'wish_job3' => null,
            'handler1' => $handler->id,
            'handler2' => null,
            'status' => $employedStatus->code,
            'decided_job' => $job->id,
            'status_changed_on' => Carbon::today()->toDateString(),
            'remind_30m_enabled' => '1',
            'notify_handlers' => '0',
            'back' => route('candidates.index'),
        ];

        $response = $this->actingAs($user)->put(route('candidates.update', $candidate), $payload);

        $response->assertRedirect(route('candidates.celebrate'));
        $this->assertSame('候補者情報を更新しました。', session('status'));

        $response->assertSessionHas('celebration.payload', function ($payload) {
            return is_array($payload)
                && ($payload['redirect_url'] ?? null) === route('candidates.index')
                && ($payload['status_label'] ?? null) === '就業決定'
                && ($payload['delay_seconds'] ?? null) === 5;
        });
    }
}
