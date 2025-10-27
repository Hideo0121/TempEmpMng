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

class CandidateKeywordSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();
        $this->seedCandidateStatus();
    }

    public function test_keyword_allows_direct_id_search(): void
    {
        [$user, $agency, $job] = $this->prepareCoreData();

        $candidate = Candidate::create([
            'id' => 7,
            'name' => '佐藤一郎',
            'name_kana' => 'サトウイチロウ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $user->id,
            'status_code' => CandidateStatus::CODE_VISIT_PENDING,
            'status_changed_on' => Carbon::today(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Candidate::create([
            'id' => 9,
            'name' => '田中花子',
            'name_kana' => 'タナカハナコ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $user->id,
            'status_code' => CandidateStatus::CODE_VISIT_PENDING,
            'status_changed_on' => Carbon::today(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('candidates.names', [
            'keyword' => 'ID:7',
        ]));

        $response->assertOk();
    $rows = $response->json('names');
    $this->assertCount(1, $rows);

    $columns = explode("\t", $rows[0]);
    $this->assertSame(['佐藤一郎', 'サトウイチロウ', '000007'], $columns);
    }

    public function test_keyword_supports_multiple_ids_with_or_logic(): void
    {
        [$user, $agency, $job] = $this->prepareCoreData();

        $candidates = [
            ['id' => 3, 'name' => '山田太郎', 'name_kana' => 'ヤマダタロウ'],
            ['id' => 8, 'name' => '伊藤次郎', 'name_kana' => 'イトウジロウ'],
            ['id' => 12, 'name' => '中村花子', 'name_kana' => 'ナカムラハナコ'],
        ];

        foreach ($candidates as $data) {
            Candidate::create([
                'id' => $data['id'],
                'name' => $data['name'],
                'name_kana' => $data['name_kana'],
                'agency_id' => $agency->id,
                'wish_job1_id' => $job->id,
                'introduced_on' => Carbon::today(),
                'handler1_user_id' => $user->id,
                'status_code' => CandidateStatus::CODE_VISIT_PENDING,
                'status_changed_on' => Carbon::today(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('candidates.names', [
            'keyword' => 'ID:3 ID:8',
            'keyword_logic' => 'or',
        ]));

        $response->assertOk();
        $rows = $response->json('names');
        $ids = array_map(static fn ($row) => substr($row, -6), $rows);
        sort($ids);

        $this->assertSame(['000003', '000008'], $ids);
    }

    public function test_keyword_supports_mixed_id_and_text_with_and_logic(): void
    {
        [$user, $agency, $job] = $this->prepareCoreData();

        Candidate::create([
            'id' => 15,
            'name' => '笠井秀夫',
            'name_kana' => 'カサイヒデオ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $user->id,
            'status_code' => CandidateStatus::CODE_VISIT_PENDING,
            'status_changed_on' => Carbon::today(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Candidate::create([
            'id' => 16,
            'name' => '笠井太郎',
            'name_kana' => 'カサイタロウ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $user->id,
            'status_code' => CandidateStatus::CODE_VISIT_PENDING,
            'status_changed_on' => Carbon::today(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('candidates.names', [
            'keyword' => 'ID:15 笠井',
            'keyword_logic' => 'and',
        ]));

        $response->assertOk();
    $rows = $response->json('names');
    $this->assertCount(1, $rows);

    $columns = explode("\t", $rows[0]);
    $this->assertSame(['笠井秀夫', 'カサイヒデオ', '000015'], $columns);
    }

    private function seedCandidateStatus(): void
    {
        CandidateStatus::create([
            'code' => CandidateStatus::CODE_VISIT_PENDING,
            'label' => '職場見学待',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);
        CandidateStatus::refreshEmployedCache();
    }

    private function prepareCoreData(): array
    {
        $user = User::factory()->create();

        $agency = Agency::create([
            'name' => '派遣会社A',
            'is_active' => true,
        ]);

        $job = JobCategory::create([
            'name' => '事務',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        return [$user, $agency, $job];
    }
}
