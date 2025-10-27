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

class CandidateAssignmentClipboardTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private JobCategory $jobCategory;
    private User $handler;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();

        CandidateStatus::create([
            'code' => CandidateStatus::CODE_VISIT_PENDING,
            'label' => '職場見学待',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);
        CandidateStatus::refreshEmployedCache();

        $this->agency = Agency::create([
            'name' => '派遣会社A',
            'is_active' => true,
        ]);

        $this->jobCategory = JobCategory::create([
            'name' => 'ピッキング',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->handler = User::factory()->create();
    }

    public function test_assignments_from_clipboard_updates_candidates(): void
    {
        $candidate = $this->createCandidate([
            'id' => 7,
            'name' => '高橋和也',
            'name_kana' => 'タカハシカズヤ',
        ]);

        $actingUser = User::factory()->create();

        $response = $this->actingAs($actingUser)->postJson(route('candidates.assignments.clipboard'), [
            'entries' => [[
                'id' => 7,
                'assignment_worker_code_a' => 'ｂ１１',
                'assignment_worker_code_b' => 'j11',
                'assignment_locker' => '１-１-１',
            ]],
        ]);

        $response->assertOk();
        $response->assertJson([
            'ids' => [7],
        ]);

        $candidate->refresh();
        $this->assertSame('B11', $candidate->assignment_worker_code_a);
        $this->assertSame('J11', $candidate->assignment_worker_code_b);
        $this->assertSame('1-1-1', $candidate->assignment_locker);
        $this->assertSame($actingUser->id, $candidate->updated_by);
    }

    public function test_assignments_from_clipboard_rejects_existing_records(): void
    {
        $candidate = $this->createCandidate([
            'id' => 3,
            'assignment_worker_code_a' => 'AA1',
        ]);

        $actingUser = User::factory()->create();

        $response = $this->actingAs($actingUser)->postJson(route('candidates.assignments.clipboard'), [
            'entries' => [[
                'id' => 3,
                'assignment_worker_code_a' => 'B22',
                'assignment_worker_code_b' => 'C22',
                'assignment_locker' => '1-2-3',
            ]],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => '既に登録があるため続行できません',
        ]);

        $candidate->refresh();
        $this->assertSame('AA1', $candidate->assignment_worker_code_a);
    }

    public function test_assignments_from_clipboard_validates_locker_format(): void
    {
        $candidate = $this->createCandidate([
            'id' => 5,
        ]);

        $actingUser = User::factory()->create();

        $response = $this->actingAs($actingUser)->postJson(route('candidates.assignments.clipboard'), [
            'entries' => [[
                'id' => 5,
                'assignment_worker_code_a' => 'B11',
                'assignment_worker_code_b' => 'C11',
                'assignment_locker' => '11-1',
            ]],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'ロッカーフォーマットが不正です',
        ]);

        $candidate->refresh();
        $this->assertNull($candidate->assignment_worker_code_a);
    }

    public function test_assignments_from_clipboard_requires_existing_candidates(): void
    {
        $actingUser = User::factory()->create();

        $response = $this->actingAs($actingUser)->postJson(route('candidates.assignments.clipboard'), [
            'entries' => [[
                'id' => 99,
                'assignment_worker_code_a' => 'B11',
                'assignment_worker_code_b' => 'C11',
                'assignment_locker' => '1-1-1',
            ]],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'ID:99 に該当する候補者が見つかりません',
        ]);
    }

    private function createCandidate(array $attributes = []): Candidate
    {
        $defaults = [
            'name' => '候補者',
            'name_kana' => 'コウホシャ',
            'agency_id' => $this->agency->id,
            'wish_job1_id' => $this->jobCategory->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $this->handler->id,
            'status_code' => CandidateStatus::CODE_VISIT_PENDING,
            'status_changed_on' => Carbon::today(),
            'created_by' => $this->handler->id,
            'updated_by' => $this->handler->id,
        ];

        $payload = array_merge($defaults, $attributes);

        return Candidate::create($payload);
    }
}
