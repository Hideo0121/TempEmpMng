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

class CandidateMemoUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();
    }

    public function test_staff_user_can_update_other_conditions_from_detail(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $agency = Agency::create([
            'name' => '派遣元A',
            'is_active' => true,
        ]);

        $status = CandidateStatus::create([
            'code' => 'ENTRY',
            'label' => 'エントリー',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);
        CandidateStatus::refreshEmployedCache();

        $jobCategory = JobCategory::create([
            'name' => '事務',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $candidate = Candidate::create([
            'name' => 'テスト候補者',
            'name_kana' => 'テストコウホシャ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $jobCategory->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $user->id,
            'status_code' => $status->code,
            'status_changed_on' => Carbon::today(),
            'other_conditions' => '初期メモ',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $backUrl = route('candidates.index');

        $response = $this->actingAs($user)->patch(
            route('candidates.memo.update', $candidate),
            [
                'other_conditions' => '更新後のメモ内容',
                'back' => $backUrl,
            ]
        );

        $response->assertRedirect(route('candidates.show', [
            'candidate' => $candidate->id,
            'back' => $backUrl,
        ]));
        $response->assertSessionHas('status', 'その他条件・メモを更新しました。');

        $candidate->refresh();

        $this->assertSame('更新後のメモ内容', $candidate->other_conditions);
        $this->assertSame($user->id, $candidate->updated_by);
    }
}
