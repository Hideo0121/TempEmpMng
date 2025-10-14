<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\Interview;
use App\Models\JobCategory;
use App\Models\User;
use App\Services\LineworksCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CandidateLineworksRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CandidateStatus::refreshEmployedCache();
    }

    public function test_can_register_lineworks_event_when_configured(): void
    {
        /** @var MockInterface $lineworks */
        $lineworks = Mockery::mock(LineworksCalendarService::class);
        $this->app->instance(LineworksCalendarService::class, $lineworks);

        $user = User::factory()->create();

        $agency = Agency::create([
            'name' => '派遣元テスト',
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

        $job = JobCategory::create([
            'name' => '事務',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $scheduledAt = Carbon::today()->setTime(10, 30);

        $candidate = Candidate::create([
            'name' => '候補者テスト',
            'name_kana' => 'コウホシャテスト',
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
            'scheduled_at' => $scheduledAt,
            'remind_30m_enabled' => true,
        ]);

        $backUrl = route('candidates.index');

        $lineworks->shouldReceive('isConfigured')->once()->andReturn(true);
        $lineworks->shouldReceive('createInterviewEvent')
            ->once()
            ->withArgs(function ($passedCandidate, $passedAt) use ($candidate, $scheduledAt) {
                $this->assertTrue($candidate->is($passedCandidate));
                $this->assertTrue($scheduledAt->equalTo($passedAt));

                return true;
            });

        $response = $this->actingAs($user)->post(
            route('candidates.lineworks.register', $candidate),
            ['back' => $backUrl]
        );

        $response->assertRedirect(route('candidates.show', [
            'candidate' => $candidate->id,
            'back' => $backUrl,
        ]));
        $response->assertSessionHas('lineworks_status', 'LINE WORKSカレンダーに登録しました。');

        $lineworks->shouldHaveReceived('createInterviewEvent');
    }

    public function test_requires_confirmed_time_before_registering(): void
    {
        /** @var MockInterface $lineworks */
        $lineworks = Mockery::mock(LineworksCalendarService::class);
        $lineworks->shouldReceive('isConfigured')->never();
        $lineworks->shouldReceive('createInterviewEvent')->never();
        $this->app->instance(LineworksCalendarService::class, $lineworks);

        $user = User::factory()->create();

        $agency = Agency::create([
            'name' => '派遣元テスト',
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

        $job = JobCategory::create([
            'name' => '事務',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $candidate = Candidate::create([
            'name' => '候補者テスト',
            'name_kana' => 'コウホシャテスト',
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
            'scheduled_at' => Carbon::today()->startOfDay(),
            'remind_30m_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post(route('candidates.lineworks.register', $candidate));

        $response->assertRedirect(route('candidates.show', ['candidate' => $candidate->id]));
        $response->assertSessionHas('lineworks_error', '見学確定日と時間の両方を設定してください。');

        $lineworks->shouldNotHaveReceived('createInterviewEvent');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
