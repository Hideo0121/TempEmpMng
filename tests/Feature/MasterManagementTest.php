<?php

namespace Tests\Feature;

use App\Models\CandidateStatus;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_master_index(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)->get(route('masters.index'));

        $response->assertOk();
        $response->assertSee('マスタ管理');
    }

    public function test_staff_cannot_access_master_routes(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('masters.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_create_job_category(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)->post(route('masters.job-categories.store'), [
            'name' => '経理アシスタント',
            'sort_order' => 10,
            'is_active' => 1,
            'is_public' => 1,
        ]);

        $response->assertRedirect(route('masters.job-categories.index'));
        $this->assertDatabaseHas('job_categories', [
            'name' => '経理アシスタント',
            'sort_order' => 10,
            'is_active' => 1,
            'is_public' => 1,
        ]);
    }

    public function test_manager_can_update_candidate_status(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $status = CandidateStatus::create([
            'code' => 'visit_pending',
            'label' => '職場見学待',
            'color_code' => '#E8F0FE',
            'sort_order' => 10,
            'is_active' => true,
            'is_employed_state' => false,
        ]);

        $response = $this->actingAs($manager)->put(route('masters.candidate-statuses.update', $status), [
            'code' => 'visit_pending',
            'label' => '見学調整中',
            'color_code' => '#ffcc00',
            'sort_order' => 5,
            'is_active' => 0,
            'is_employed_state' => 1,
        ]);

        $response->assertRedirect(route('masters.candidate-statuses.index'));
        $this->assertDatabaseHas('candidate_statuses', [
            'code' => 'visit_pending',
            'label' => '見学調整中',
            'color_code' => '#FFCC00',
            'sort_order' => 5,
            'is_active' => 0,
            'is_employed_state' => 1,
        ]);
    }

    public function test_manager_can_view_recruitment_info_index(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $category = JobCategory::create([
            'name' => '一般事務',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $category->recruitmentInfo()->create([
            'planned_hires' => 3,
            'comment' => '即勤務できる方を募集中',
        ]);

        $response = $this->actingAs($manager)->get(route('masters.recruitment-info.index'));

        $response->assertOk();
        $response->assertSee('募集情報一覧');
        $response->assertSee('一般事務');
        $response->assertSee('3');
    }

    public function test_manager_can_update_recruitment_info(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $category = JobCategory::create([
            'name' => '受付スタッフ',
            'sort_order' => 2,
            'is_active' => true,
            'is_public' => true,
        ]);

        $response = $this->actingAs($manager)->put(route('masters.recruitment-info.update', ['jobCategory' => $category]), [
            'planned_hires' => 5,
            'recruitment_comment' => '午後シフト歓迎',
        ]);

        $response->assertRedirect(route('masters.recruitment-info.index'));

        $this->assertDatabaseHas('recruitment_info', [
            'job_category_id' => $category->id,
            'planned_hires' => 5,
            'comment' => '午後シフト歓迎',
        ]);
    }
}
