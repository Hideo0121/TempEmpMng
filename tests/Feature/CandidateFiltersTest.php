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

class CandidateFiltersTest extends TestCase
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
            'name' => 'テスト派遣元',
            'is_active' => true,
        ]);

        $status = CandidateStatus::create([
            'code' => 'ENTRY',
            'label' => 'エントリー',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);

        $jobA = JobCategory::create([
            'name' => '事務',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $jobB = JobCategory::create([
            'name' => '販売',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        CandidateStatus::refreshEmployedCache();

        return compact('agency', 'status', 'jobA', 'jobB');
    }

    private function createCandidate(User $creator, Agency $agency, CandidateStatus $status, JobCategory $job, string $name, ?JobCategory $decidedJob = null): Candidate
    {
        return Candidate::create([
            'name' => $name,
            'name_kana' => $name,
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'decided_job_category_id' => $decidedJob?->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $creator->id,
            'status_code' => $status->code,
            'status_changed_on' => Carbon::today(),
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);
    }

    public function test_can_filter_candidates_by_reminder_state(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'jobA' => $jobA] = $this->seedLookups();

        $candidateOn = $this->createCandidate($user, $agency, $status, $jobA, 'リマインドON');
        Interview::create([
            'candidate_id' => $candidateOn->id,
            'scheduled_at' => Carbon::tomorrow()->setTime(10, 0),
            'remind_30m_enabled' => true,
        ]);

        $candidateOff = $this->createCandidate($user, $agency, $status, $jobA, 'リマインドOFF');
        Interview::create([
            'candidate_id' => $candidateOff->id,
            'scheduled_at' => Carbon::tomorrow()->setTime(11, 0),
            'remind_30m_enabled' => false,
        ]);

        $responseOn = $this->actingAs($user)->get(route('candidates.index', ['remind_30m' => 'on']));
        $responseOn->assertOk();
        $responseOn->assertSeeText('リマインドON');
        $responseOn->assertDontSeeText('リマインドOFF');

        $responseOff = $this->actingAs($user)->get(route('candidates.index', ['remind_30m' => 'off']));
        $responseOff->assertOk();
        $responseOff->assertSeeText('リマインドOFF');
        $responseOff->assertDontSeeText('リマインドON');
    }

    public function test_can_filter_candidates_by_wish_job(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'jobA' => $jobA, 'jobB' => $jobB] = $this->seedLookups();

        $this->createCandidate($user, $agency, $status, $jobA, '事務希望');
        $this->createCandidate($user, $agency, $status, $jobB, '販売希望');

        $response = $this->actingAs($user)->get(route('candidates.index', ['wish_job' => $jobA->id]));

        $response->assertOk();
        $response->assertSeeText('事務希望');
        $response->assertDontSeeText('販売希望');
    }

    public function test_candidate_store_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'jobA' => $jobA] = $this->seedLookups();

        $payload = [
            'name' => '新規候補者',
            'name_kana' => 'シンキコウホシャ',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $jobA->id,
            'handler1' => $user->id,
            'status' => $status->code,
            'status_changed_on' => Carbon::today()->toDateString(),
            'visit_confirmed_date' => Carbon::tomorrow()->toDateString(),
            'visit_confirmed_time' => '10:30',
            'remind_30m_enabled' => '1',
        ];

        $response = $this->actingAs($user)->post(route('candidates.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('candidates', [
            'name' => '新規候補者',
            'agency_id' => $agency->id,
        ]);

        $this->assertDatabaseHas('interviews', [
            'remind_30m_enabled' => true,
        ]);
    }

    public function test_requires_decided_job_when_status_employed(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'jobA' => $jobA] = $this->seedLookups();

        $employedStatus = CandidateStatus::create([
            'code' => CandidateStatus::CODE_EMPLOYED,
            'label' => '就業決定',
            'sort_order' => 99,
            'is_active' => true,
            'is_employed_state' => true,
        ]);
        CandidateStatus::refreshEmployedCache();

        $payload = [
            'name' => '就業決定テスト',
            'name_kana' => 'シュウギョウケッテイテスト',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $jobA->id,
            'handler1' => $user->id,
            'status' => $employedStatus->code,
            'status_changed_on' => Carbon::today()->toDateString(),
        ];

        $response = $this->actingAs($user)->post(route('candidates.store'), $payload);

        $response->assertSessionHasErrors('decided_job');

        $payload['decided_job'] = $jobA->id;

        $response = $this->actingAs($user)->post(route('candidates.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('candidates', [
            'name' => '就業決定テスト',
            'decided_job_category_id' => $jobA->id,
        ]);
    }

    public function test_cannot_set_decided_job_when_status_not_employed(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'jobA' => $jobA] = $this->seedLookups();

        $payload = [
            'name' => '誤設定テスト',
            'name_kana' => 'ゴセッテイテスト',
            'agency_id' => $agency->id,
            'introduced_on' => Carbon::today()->toDateString(),
            'wish_job1' => $jobA->id,
            'handler1' => $user->id,
            'status' => $status->code,
            'status_changed_on' => Carbon::today()->toDateString(),
            'decided_job' => $jobA->id,
        ];

        $response = $this->actingAs($user)->post(route('candidates.store'), $payload);

        $response->assertSessionHasErrors('decided_job');
    }

    public function test_candidate_detail_contains_back_link_with_filters(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'jobA' => $jobA] = $this->seedLookups();

        $candidate = $this->createCandidate($user, $agency, $status, $jobA, 'バックリンクテスト');

        $backUrl = route('candidates.index', ['keyword' => '検索']);

        $response = $this->actingAs($user)->get(route('candidates.show', ['candidate' => $candidate, 'back' => $backUrl]));

        $response->assertOk();
        $response->assertSee('href="' . e($backUrl) . '"', false);
    }

    public function test_can_filter_candidates_by_decided_job(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $entryStatus, 'jobA' => $jobA, 'jobB' => $jobB] = $this->seedLookups();

        $employedStatus = CandidateStatus::create([
            'code' => CandidateStatus::CODE_EMPLOYED,
            'label' => '就業決定',
            'sort_order' => 99,
            'is_active' => true,
            'is_employed_state' => true,
        ]);
        CandidateStatus::refreshEmployedCache();

        $this->createCandidate($user, $agency, $entryStatus, $jobA, 'エントリーA');
        $this->createCandidate($user, $agency, $employedStatus, $jobB, '就業決定B', $jobB);

        $response = $this->actingAs($user)->get(route('candidates.index', ['decided_job' => $jobB->id]));

        $response->assertOk();
        $response->assertSeeText('就業決定B');
        $response->assertDontSeeText('エントリーA');
        $response->assertSeeText($jobB->name);
    }

    public function test_status_change_requires_decided_job_when_employed(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $entryStatus, 'jobA' => $jobA] = $this->seedLookups();

        $employedStatus = CandidateStatus::create([
            'code' => CandidateStatus::CODE_EMPLOYED,
            'label' => '就業決定',
            'sort_order' => 99,
            'is_active' => true,
            'is_employed_state' => true,
        ]);
        CandidateStatus::refreshEmployedCache();

        $candidate = $this->createCandidate($user, $agency, $entryStatus, $jobA, 'ステータス変更候補');

        $this->actingAs($user)
            ->patchJson(route('candidates.status.update', $candidate), [
                'status_code' => CandidateStatus::CODE_EMPLOYED,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('decided_job');

        $this->actingAs($user)
            ->patchJson(route('candidates.status.update', $candidate), [
                'status_code' => CandidateStatus::CODE_EMPLOYED,
                'decided_job' => $jobA->id,
            ])
            ->assertOk()
            ->assertJson(["decided_job_id" => $jobA->id]);

        $this->assertDatabaseHas('candidates', [
            'id' => $candidate->id,
            'status_code' => CandidateStatus::CODE_EMPLOYED,
            'decided_job_category_id' => $jobA->id,
        ]);
    }

    public function test_can_sort_candidates_by_name(): void
    {
        $user = User::factory()->create();
        ['agency' => $agency, 'status' => $status, 'jobA' => $jobA] = $this->seedLookups();

        $this->createCandidate($user, $agency, $status, $jobA, 'Chris Carter');
        $this->createCandidate($user, $agency, $status, $jobA, 'Adam Adams');
        $this->createCandidate($user, $agency, $status, $jobA, 'Bella Barnes');

        $responseAsc = $this->actingAs($user)->get(route('candidates.index', ['sort' => 'name', 'direction' => 'asc']));
        $responseAsc->assertOk();
        $responseAsc->assertSeeInOrder(['Adam Adams', 'Bella Barnes', 'Chris Carter']);

        $responseDesc = $this->actingAs($user)->get(route('candidates.index', ['sort' => 'name', 'direction' => 'desc']));
        $responseDesc->assertOk();
        $responseDesc->assertSeeInOrder(['Chris Carter', 'Bella Barnes', 'Adam Adams']);
    }
}
