<?php

namespace Tests\Feature;

use App\Models\JobCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentStatusVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_categories_are_not_displayed(): void
    {
        $public = JobCategory::create([
            'name' => '公開職種',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        $public->recruitmentInfo()->create([
            'planned_hires' => 5,
            'comment' => '公開コメント',
        ]);

        $hidden = JobCategory::create([
            'name' => '非公開職種',
            'sort_order' => 2,
            'is_active' => true,
            'is_public' => false,
        ]);

        $hidden->recruitmentInfo()->create([
            'planned_hires' => 3,
            'comment' => '非公開コメント',
        ]);

        $response = $this->get(route('recruitment.status'));

        $response->assertOk();
        $response->assertSee('公開職種');
        $response->assertSee('公開コメント');
        $response->assertDontSee('非公開職種');
        $response->assertDontSee('非公開コメント');
    }

    public function test_shows_empty_state_when_no_public_categories(): void
    {
        JobCategory::create([
            'name' => '内部向け職種',
            'sort_order' => 1,
            'is_active' => true,
            'is_public' => false,
        ]);

        $response = $this->get(route('recruitment.status'));

        $response->assertOk();
        $response->assertSee('表示できる募集情報がありません。');
    }
}
