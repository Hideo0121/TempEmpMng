<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\JobCategory;
use Illuminate\Contracts\View\View;

class RecruitmentStatusController extends Controller
{
    public function __invoke(): View
    {
        $employedStatusCodes = CandidateStatus::query()
            ->where('is_employed_state', true)
            ->pluck('code')
            ->all();

        $employedCounts = Candidate::query()
            ->selectRaw('decided_job_category_id, COUNT(*) as total')
            ->whereNotNull('decided_job_category_id')
            ->when(!empty($employedStatusCodes), function ($query) use ($employedStatusCodes) {
                $query->whereIn('status_code', $employedStatusCodes);
            })
            ->groupBy('decided_job_category_id')
            ->pluck('total', 'decided_job_category_id');

        $categories = JobCategory::query()
            ->with('recruitmentInfo')
            ->where('is_active', true)
            ->where('is_public', true)
            ->where('name', '!=', '短期')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (JobCategory $category) use ($employedCounts) {
                $planned = (int) optional($category->recruitmentInfo)->planned_hires;
                $comment = optional($category->recruitmentInfo)->comment;
                $decided = (int) ($employedCounts[$category->id] ?? 0);

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'planned' => $planned,
                    'decided' => $decided,
                    'difference' => $planned - $decided,
                    'comment' => $comment,
                ];
            });

        return view('recruitment.status', [
            'categories' => $categories,
        ]);
    }
}
