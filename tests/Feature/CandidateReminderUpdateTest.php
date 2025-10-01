<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\Interview;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CandidateReminderUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function createLookupData(): array
    {
        $agency = Agency::create([
            'name' => '派遣元A',
            'is_active' => true,
        ]);

        $status = CandidateStatus::create([
            'code' => 'ENTRY',
            'label' => 'エントリー',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $job = JobCategory::create([
            'name' => '事務職',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return compact('agency', 'status', 'job');
    }

    private function createCandidateWithInterview(User $user, Agency $agency, CandidateStatus $status, JobCategory $job, bool $remindEnabled = true): Candidate
    {
        $candidate = Candidate::create([
            'name' => '田中太郎',
            'name_kana' => 'タナカタロウ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $user->id,
            'status_code' => $status->code,
            'status_changed_on' => Carbon::today(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Interview::create([
            'candidate_id' => $candidate->id,
            'scheduled_at' => Carbon::tomorrow()->setTime(10, 30),
            'remind_30m_enabled' => $remindEnabled,
        ]);

        return $candidate->fresh(['confirmedInterview']);
    }

    private function updatePayload(Candidate $candidate, JobCategory $job, User $user, CandidateStatus $status, string $reminderValue): array
    {
        $confirmed = $candidate->confirmedInterview;
        $scheduledAt = optional($confirmed)->scheduled_at ?? Carbon::tomorrow()->setTime(10, 30);

        return [
            'name' => $candidate->name,
            'name_kana' => $candidate->name_kana,
            'agency_id' => $candidate->agency_id,
            'introduced_on' => $candidate->introduced_on->toDateString(),
            'wish_job1' => $job->id,
            'handler1' => $user->id,
            'status' => $status->code,
            'status_changed_on' => $candidate->status_changed_on->toDateString(),
            'visit_confirmed_date' => $scheduledAt->toDateString(),
            'visit_confirmed_time' => $scheduledAt->format('H:i'),
            'remind_30m_enabled' => $reminderValue,
        ];
    }

    public function test_can_toggle_reminder_flag_off(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'job' => $job] = $this->createLookupData();
        $candidate = $this->createCandidateWithInterview($user, $agency, $status, $job, true);

        $payload = $this->updatePayload($candidate, $job, $user, $status, '0');

        $response = $this->actingAs($user)->put(route('candidates.update', $candidate), $payload);

    $response->assertRedirect(route('candidates.index'));

        $candidate->refresh();
        $this->assertFalse($candidate->confirmedInterview->remind_30m_enabled);
        $this->assertDatabaseHas('interviews', [
            'candidate_id' => $candidate->id,
            'remind_30m_enabled' => false,
        ]);
    }

    public function test_candidate_detail_displays_reminder_flag(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'job' => $job] = $this->createLookupData();
        $candidate = $this->createCandidateWithInterview($user, $agency, $status, $job, false);

        $response = $this->actingAs($user)->get(route('candidates.show', $candidate));

        $response->assertOk();
        $response->assertSeeText('30分前リマインドを送信する');
        $response->assertSeeText('OFF');
    }
}
