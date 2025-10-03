<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecruitmentInfoRequest;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\JobCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RecruitmentInfoController extends Controller
{
    public function index(): View
    {
        $employedStatusCodes = CandidateStatus::query()
            ->where('is_employed_state', true)
            ->pluck('code');

        $employedCounts = Candidate::query()
            ->selectRaw('decided_job_category_id, COUNT(*) as total')
            ->whereNotNull('decided_job_category_id')
            ->when($employedStatusCodes->isNotEmpty(), function ($query) use ($employedStatusCodes) {
                $query->whereIn('status_code', $employedStatusCodes->all());
            })
            ->groupBy('decided_job_category_id')
            ->pluck('total', 'decided_job_category_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $categories = JobCategory::query()
            ->with('recruitmentInfo')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('masters.recruitment-info.index', [
            'categories' => $categories,
            'employedCounts' => $employedCounts,
        ]);
    }

    public function edit(JobCategory $jobCategory): View
    {
        $jobCategory->loadMissing('recruitmentInfo');

        $employedStatusCodes = CandidateStatus::query()
            ->where('is_employed_state', true)
            ->pluck('code');

        $decidedCount = Candidate::query()
            ->where('decided_job_category_id', $jobCategory->id)
            ->when($employedStatusCodes->isNotEmpty(), function ($query) use ($employedStatusCodes) {
                $query->whereIn('status_code', $employedStatusCodes->all());
            })
            ->count();

        $planned = (int) optional($jobCategory->recruitmentInfo)->planned_hires;

        return view('masters.recruitment-info.edit', [
            'category' => $jobCategory,
            'decidedCount' => $decidedCount,
            'difference' => $planned - $decidedCount,
        ]);
    }

    public function update(RecruitmentInfoRequest $request, JobCategory $jobCategory): RedirectResponse
    {
        $validated = $request->validated();

        $planned = $validated['planned_hires'] ?? 0;
        $comment = $validated['recruitment_comment'] ?? null;

        $jobCategory->recruitmentInfo()->updateOrCreate([], [
            'planned_hires' => $planned,
            'comment' => $comment,
        ]);

        return redirect()
            ->route('masters.recruitment-info.index')
            ->with('status', '募集情報を更新しました。');
    }
}
