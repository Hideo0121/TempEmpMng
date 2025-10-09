<?php

namespace App\Http\Controllers;

use App\Models\CandidateStatus;
use App\Models\Interview;
use App\Models\JobCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();
        $todayVisitCount = Interview::query()
            ->whereDate('scheduled_at', $today)
            ->count();

        $statuses = CandidateStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['code', 'label']);

        $jobCategories = JobCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $employedStatusCodes = CandidateStatus::employedCodes();
        $lowerStatusColumn = DB::raw('lower(status_code)');

        $wishJobStatusTotals = DB::query()
            ->fromSub(function ($query) use ($lowerStatusColumn, $employedStatusCodes) {
                $query->selectRaw('wish_job1_id as job_category_id, status_code')
                    ->from('candidates')
                    ->whereNotNull('wish_job1_id')
                    ->when($employedStatusCodes, fn ($q) => $q->whereNotIn($lowerStatusColumn, $employedStatusCodes))
                    ->unionAll(
                        DB::table('candidates')
                            ->selectRaw('wish_job2_id as job_category_id, status_code')
                            ->whereNotNull('wish_job2_id')
                            ->when($employedStatusCodes, fn ($q) => $q->whereNotIn($lowerStatusColumn, $employedStatusCodes))
                    )
                    ->unionAll(
                        DB::table('candidates')
                            ->selectRaw('wish_job3_id as job_category_id, status_code')
                            ->whereNotNull('wish_job3_id')
                            ->when($employedStatusCodes, fn ($q) => $q->whereNotIn($lowerStatusColumn, $employedStatusCodes))
                    );
            }, 'candidate_wishes')
            ->selectRaw('job_category_id, status_code, COUNT(*) as total')
            ->groupBy('job_category_id', 'status_code')
            ->get()
            ->groupBy('job_category_id');

        $decidedJobTotals = DB::table('candidates')
            ->selectRaw('decided_job_category_id as job_category_id, COUNT(*) as total')
            ->when($employedStatusCodes, fn ($q) => $q->whereIn($lowerStatusColumn, $employedStatusCodes))
            ->whereNotNull('decided_job_category_id')
            ->groupBy('decided_job_category_id')
            ->get()
            ->keyBy('job_category_id');

        $wishJobMatrix = [];
        $wishJobRowTotals = [];
        $wishJobColumnTotals = array_fill_keys($statuses->pluck('code')->all(), 0);
        $wishJobGrandTotal = 0;

        foreach ($jobCategories as $jobCategory) {
            foreach ($statuses as $status) {
                if (CandidateStatus::isEmployed((string) $status->code)) {
                    $count = (int) optional($decidedJobTotals->get($jobCategory->id))->total;
                } else {
                    $count = (int) optional($wishJobStatusTotals->get($jobCategory->id))
                        ?->firstWhere('status_code', $status->code)
                        ?->total;
                }

                $wishJobMatrix[$jobCategory->id][$status->code] = $count;
                $wishJobRowTotals[$jobCategory->id] = ($wishJobRowTotals[$jobCategory->id] ?? 0) + $count;
                $wishJobColumnTotals[$status->code] += $count;
                $wishJobGrandTotal += $count;
            }
        }

        $dateRange = collect(range(0, 6))->map(fn (int $offset) => $today->copy()->addDays($offset));
        $startOfRange = $dateRange->first()->copy()->startOfDay();
        $endOfRange = $dateRange->last()->copy()->endOfDay();

        $interviews = Interview::query()
            ->with(['candidate:id,handler1_user_id,handler2_user_id'])
            ->whereBetween('scheduled_at', [$startOfRange, $endOfRange])
            ->get();

        $handlerVisitMatrix = [];
        foreach ($interviews as $interview) {
            $candidate = $interview->candidate;
            if (!$candidate) {
                continue;
            }

            $dateKey = $interview->scheduled_at->toDateString();
            $handlerIds = collect([$candidate->handler1_user_id, $candidate->handler2_user_id])
                ->filter()
                ->unique();

            foreach ($handlerIds as $userId) {
                $handlerVisitMatrix[$userId][$dateKey] = ($handlerVisitMatrix[$userId][$dateKey] ?? 0) + 1;
            }
        }

        $handlers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $handlerRowTotals = [];
        $visitDateTotals = array_fill_keys($dateRange->map->toDateString()->all(), 0);
        $handlerGrandTotal = 0;

        foreach ($handlers as $handler) {
            foreach ($dateRange as $date) {
                $dateKey = $date->toDateString();
                $count = $handlerVisitMatrix[$handler->id][$dateKey] ?? 0;
                $handlerRowTotals[$handler->id] = ($handlerRowTotals[$handler->id] ?? 0) + $count;
                $visitDateTotals[$dateKey] = ($visitDateTotals[$dateKey] ?? 0) + $count;
                $handlerGrandTotal += $count;
            }
        }

        $employmentStartStats = DB::table('candidates')
            ->selectRaw('decided_job_category_id as job_category_id, DATE(employment_start_at) as start_date, COUNT(*) as total')
            ->whereNotNull('decided_job_category_id')
            ->whereNotNull('employment_start_at')
            ->whereDate('employment_start_at', '>=', $today)
            ->groupByRaw('decided_job_category_id, DATE(employment_start_at)')
            ->get();

        $employmentStartDates = $employmentStartStats
            ->pluck('start_date')
            ->unique()
            ->sort()
            ->map(fn (string $date) => Carbon::createFromFormat('Y-m-d', $date));

        $employmentStartMatrix = [];
        $employmentStartRowTotals = [];
        $employmentStartColumnTotals = [];
        foreach ($employmentStartDates as $date) {
            $employmentStartColumnTotals[$date->toDateString()] = 0;
        }
        $employmentStartGrandTotal = 0;

        foreach ($employmentStartStats as $stat) {
            $dateKey = $stat->start_date;
            $jobCategoryId = (int) $stat->job_category_id;
            $total = (int) $stat->total;

            $employmentStartMatrix[$jobCategoryId][$dateKey] = $total;
            $employmentStartRowTotals[$jobCategoryId] = ($employmentStartRowTotals[$jobCategoryId] ?? 0) + $total;
            $employmentStartColumnTotals[$dateKey] = ($employmentStartColumnTotals[$dateKey] ?? 0) + $total;
            $employmentStartGrandTotal += $total;
        }

        $employmentStartCategoryIds = array_keys($employmentStartMatrix);
        $employmentStartCategories = $jobCategories
            ->filter(fn ($jobCategory) => in_array($jobCategory->id, $employmentStartCategoryIds, true))
            ->values();

        return view('dashboard', [
            'today' => $today,
            'todayVisitCount' => $todayVisitCount,
            'statuses' => $statuses,
            'jobCategories' => $jobCategories,
            'wishJobMatrix' => $wishJobMatrix,
            'wishJobRowTotals' => $wishJobRowTotals,
            'wishJobColumnTotals' => $wishJobColumnTotals,
            'wishJobGrandTotal' => $wishJobGrandTotal,
            'dateRange' => $dateRange,
            'handlers' => $handlers,
            'handlerVisitMatrix' => $handlerVisitMatrix,
            'handlerRowTotals' => $handlerRowTotals,
            'visitDateTotals' => $visitDateTotals,
            'handlerGrandTotal' => $handlerGrandTotal,
            'employmentStartCategories' => $employmentStartCategories,
            'employmentStartDates' => $employmentStartDates,
            'employmentStartMatrix' => $employmentStartMatrix,
            'employmentStartRowTotals' => $employmentStartRowTotals,
            'employmentStartColumnTotals' => $employmentStartColumnTotals,
            'employmentStartGrandTotal' => $employmentStartGrandTotal,
        ]);
    }
}
