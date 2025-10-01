<?php

namespace Tests\Unit;

use App\Mail\CandidateAssignmentMail;
use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CandidateAssignmentMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_includes_wish_job_lines(): void
    {
        $creator = User::factory()->create();
        $recipient = User::factory()->create();

        $agency = Agency::create([
            'name' => 'テスト派遣会社',
            'is_active' => true,
        ]);

        $status = CandidateStatus::create([
            'code' => 'ENTRY',
            'label' => 'エントリー',
            'sort_order' => 1,
            'is_active' => true,
            'is_employed_state' => false,
        ]);

        $job1 = JobCategory::create([
            'name' => '第一希望職種',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $job2 = JobCategory::create([
            'name' => '第二希望職種',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $job3 = JobCategory::create([
            'name' => '第三希望職種',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $candidate = Candidate::create([
            'name' => '山田 太郎',
            'name_kana' => 'ヤマダ タロウ',
            'agency_id' => $agency->id,
            'wish_job1_id' => $job1->id,
            'wish_job2_id' => $job2->id,
            'wish_job3_id' => $job3->id,
            'introduced_on' => Carbon::today(),
            'handler1_user_id' => $creator->id,
            'status_code' => $status->code,
            'status_changed_on' => Carbon::today(),
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        $mail = new CandidateAssignmentMail($candidate->fresh(), $recipient, true, $creator);
        $rendered = $mail->build()->render();

        $this->assertStringContainsString('希望職種①: '.$job1->name, $rendered);
        $this->assertStringContainsString('希望職種②: '.$job2->name, $rendered);
        $this->assertStringContainsString('希望職種③: '.$job3->name, $rendered);
    }
}
