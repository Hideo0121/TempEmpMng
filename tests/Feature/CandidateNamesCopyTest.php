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

class CandidateNamesCopyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();
    }

    public function test_names_endpoint_returns_tab_delimited_rows(): void
    {
        CandidateStatus::create([
            'code' => CandidateStatus::CODE_VISIT_PENDING,
            'label' => '職場見学待',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);
        CandidateStatus::refreshEmployedCache();

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

        $candidate = Candidate::create([
            'name' => '田中太郎',
            'name_kana' => 'タナカタロウ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $user->id,
            'status_code' => CandidateStatus::CODE_VISIT_PENDING,
            'status_changed_on' => Carbon::today(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('candidates.names'));
        $response->assertOk();

        $rows = $response->json('names');
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);

        $expectedId = str_pad((string) $candidate->id, 6, '0', STR_PAD_LEFT);
        $this->assertSame("田中太郎\tタナカタロウ\t{$expectedId}", $rows[0]);
    }
}
