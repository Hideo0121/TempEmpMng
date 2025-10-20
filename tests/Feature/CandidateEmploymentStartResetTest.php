<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CandidateEmploymentStartResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();
    }

    private function seedStatuses(): void
    {
        CandidateStatus::create([
            'code' => CandidateStatus::CODE_VISIT_PENDING,
            'label' => '職場見学待',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);

        CandidateStatus::create([
            'code' => CandidateStatus::CODE_EMPLOYED,
            'label' => '就業決定',
            'sort_order' => 2,
            'is_active' => true,
            'is_employed_state' => true,
        ]);

        CandidateStatus::refreshEmployedCache();
    }

    public function test_change_status_to_non_employed_clears_employment_start(): void
    {
        $this->seedStatuses();

        $agency = Agency::create([
            'name' => '派遣元A',
            'is_active' => true,
        ]);

        $job = JobCategory::create([
            'name' => '事務',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $user = User::factory()->create();

        $startDate = Carbon::today();

        $candidate = Candidate::create([
            'name' => '候補者A',
            'name_kana' => 'コウホシャエー',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => $startDate->copy(),
            'handler1_user_id' => $user->id,
            'status_code' => CandidateStatus::CODE_EMPLOYED,
            'status_changed_on' => $startDate->copy(),
            'decided_job_category_id' => $job->id,
            'employment_start_at' => $startDate->copy()->addDays(5)->setTime(9, 0),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->patch(route('candidates.status.update', $candidate), [
            'status_code' => CandidateStatus::CODE_VISIT_PENDING,
        ]);

        $response->assertOk();
        $this->assertNull($candidate->fresh()->employment_start_at);
    }

    public function test_updating_candidate_to_non_employed_clears_employment_start(): void
    {
        $this->seedStatuses();

        $agency = Agency::create([
            'name' => '派遣元B',
            'is_active' => true,
        ]);

        $wishJob = JobCategory::create([
            'name' => '販売',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $decidedJob = JobCategory::create([
            'name' => '受付',
            'sort_order' => 2,
            'is_active' => true,
            'is_public' => true,
        ]);

        $user = User::factory()->create();

        $today = Carbon::today();
        $employmentStart = $today->copy()->addDays(3)->setTime(10, 0);

        $candidate = Candidate::create([
            'name' => '候補者B',
            'name_kana' => 'コウホシャビー',
            'agency_id' => $agency->id,
            'wish_job1_id' => $wishJob->id,
            'decided_job_category_id' => $decidedJob->id,
            'introduced_on' => $today->copy(),
            'handler1_user_id' => $user->id,
            'status_code' => CandidateStatus::CODE_EMPLOYED,
            'status_changed_on' => $today->copy(),
            'employment_start_at' => $employmentStart,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $payload = [
            'name' => '候補者B',
            'name_kana' => 'コウホシャビー',
            'agency_id' => $agency->id,
            'introduced_on' => $today->toDateString(),
            'wish_job1' => $wishJob->id,
            'wish_job2' => null,
            'wish_job3' => null,
            'decided_job' => null,
            'visit_candidate1_date' => null,
            'visit_candidate1_time' => null,
            'visit_candidate2_date' => null,
            'visit_candidate2_time' => null,
            'visit_candidate3_date' => null,
            'visit_candidate3_time' => null,
            'visit_confirmed_date' => null,
            'visit_confirmed_time' => null,
            'employment_start_date' => $employmentStart->toDateString(),
            'employment_start_time' => $employmentStart->format('H:i'),
            'assignment_worker_code_a' => null,
            'assignment_worker_code_b' => null,
            'assignment_locker' => null,
            'handler1' => $user->id,
            'handler2' => null,
            'transport_day' => null,
            'transport_month' => null,
            'other_conditions' => null,
            'introduction_note' => null,
            'status' => CandidateStatus::CODE_VISIT_PENDING,
            'status_changed_on' => $today->toDateString(),
        ];

        $response = $this->actingAs($user)->put(route('candidates.update', $candidate), $payload);

        $response->assertRedirect(route('candidates.index'));

        $candidate->refresh();
        $this->assertNull($candidate->employment_start_at);
        $this->assertNull($candidate->decided_job_category_id);
    }
}
