<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\Interview;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();
    }

    public function test_dashboard_provides_aggregated_counts(): void
    {
        $user = User::factory()->create();

        $agency = Agency::create([
            'name' => '派遣元A',
            'is_active' => true,
        ]);

        $statusVisitPending = CandidateStatus::create([
            'code' => CandidateStatus::CODE_VISIT_PENDING,
            'label' => '職場見学待',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);

        $statusVisitConfirmed = CandidateStatus::create([
            'code' => 'visit_confirmed',
            'label' => '見学確定',
            'sort_order' => 2,
            'is_active' => true,
            'is_employed_state' => false,
        ]);

        $statusEmployed = CandidateStatus::create([
            'code' => CandidateStatus::CODE_EMPLOYED,
            'label' => '就業決定',
            'sort_order' => 3,
            'is_active' => true,
            'is_employed_state' => true,
        ]);
        CandidateStatus::refreshEmployedCache();

        $todayDate = Carbon::today();

        $jobOffice = JobCategory::create([
            'name' => '事務',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $jobSales = JobCategory::create([
            'name' => '販売',
            'sort_order' => 2,
            'is_active' => true,
            'is_public' => true,
        ]);

        $candidateToday = Candidate::create([
            'name' => '今日の候補者',
            'name_kana' => 'キョウノコウホシャ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $jobOffice->id,
            'introduced_on' => $todayDate->copy(),
            'handler1_user_id' => $user->id,
            'status_code' => $statusVisitPending->code,
            'status_changed_on' => $todayDate->copy(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $candidateNext = Candidate::create([
            'name' => '明日の候補者',
            'name_kana' => 'アシタノコウホシャ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $jobSales->id,
            'introduced_on' => $todayDate->copy(),
            'handler1_user_id' => $user->id,
            'handler2_user_id' => $user->id,
            'status_code' => $statusVisitConfirmed->code,
            'status_changed_on' => $todayDate->copy(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $candidateEmployed = Candidate::create([
            'name' => '決定済み候補者',
            'name_kana' => 'ケッテイズミコウホシャ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $jobSales->id,
            'decided_job_category_id' => $jobOffice->id,
            'introduced_on' => $todayDate->copy(),
            'handler1_user_id' => $user->id,
            'status_code' => $statusEmployed->code,
            'status_changed_on' => $todayDate->copy(),
            'employment_start_at' => $todayDate->copy()->addDays(3)->setTime(10, 0),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Interview::create([
            'candidate_id' => $candidateToday->id,
            'scheduled_at' => $todayDate->copy()->setTime(9, 0),
            'remind_30m_enabled' => true,
        ]);

        Interview::create([
            'candidate_id' => $candidateNext->id,
            'scheduled_at' => $todayDate->copy()->addDay()->setTime(14, 0),
            'remind_30m_enabled' => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('todayVisitCount', 1);
        $response->assertViewHas('wishJobGrandTotal', 3);
        $response->assertViewHas('handlerGrandTotal', 2);
        $response->assertViewHas('employmentStartGrandTotal', 1);

        $response->assertSeeText('希望職種 × ステータス集計');
        $response->assertSeeText('対応者 × 見学確定日集計');
        $response->assertSeeText('就業開始 × 職種集計');
        $response->assertSeeText($jobOffice->name);
        $response->assertSeeText($jobSales->name);
        $response->assertSeeText('就業決定');
        $response->assertSeeText('決定済み候補者');

    $today = $todayDate->toDateString();
    $rangeEnd = $todayDate->copy()->addDays(6)->toDateString();

        $cellUrl = route('candidates.index', [
            'handler' => $user->id,
            'interview_from' => $today,
            'interview_to' => $today,
        ]);

        $rowUrl = route('candidates.index', [
            'handler' => $user->id,
            'interview_from' => $today,
            'interview_to' => $rangeEnd,
        ]);

        $response->assertSee('href="' . e($cellUrl) . '"', false);
        $response->assertSee('href="' . e($rowUrl) . '"', false);

        $wishJobQuery = Arr::query([
            'wish_job[]' => $jobOffice->id,
            'status[]' => CandidateStatus::CODE_VISIT_PENDING,
        ]);
        $wishJobUrl = route('candidates.index') . '?' . $wishJobQuery;

        $response->assertSee('href="' . e($wishJobUrl) . '"', false);

        $employmentQuery = Arr::query([
            'decided_job[]' => $jobOffice->id,
            'status[]' => CandidateStatus::CODE_EMPLOYED,
            'employment_start_from' => $today,
            'employment_start_to' => $todayDate->copy()->addDays(7)->toDateString(),
        ]);
        $employmentUrl = route('candidates.index') . '?' . $employmentQuery;

        $response->assertSee('href="' . e($employmentUrl) . '"', false);

        $searchResponse = $this->actingAs($user)->get(route('candidates.index', ['keyword' => '候補者', 'keyword_logic' => 'and']));
        $searchResponse->assertOk();
        $searchResponse->assertSeeText('今日の候補者');
        $searchResponse->assertSeeText('明日の候補者');

        $andResponse = $this->actingAs($user)->get(route('candidates.index', ['keyword' => '今日 候補者', 'keyword_logic' => 'and']));
        $andResponse->assertOk();
        $andResponse->assertSeeText('今日の候補者');
        $andResponse->assertDontSeeText('明日の候補者');

        $orResponse = $this->actingAs($user)->get(route('candidates.index', ['keyword' => '今日 明日', 'keyword_logic' => 'or']));
        $orResponse->assertOk();
        $orResponse->assertSeeText('今日の候補者');
        $orResponse->assertSeeText('明日の候補者');
    }
}
