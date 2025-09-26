<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\JobCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Candidate::query()
            ->with([
                'agency',
                'status',
                'handler1',
                'handler2',
                'wishJob1',
                'wishJob2',
                'wishJob3',
                'interviews' => fn ($q) => $q->orderByDesc('scheduled_at'),
                'views' => fn ($q) => $user ? $q->where('user_id', $user->id) : $q->whereRaw('1 = 0'),
            ]);

        if ($keyword = $request->string('keyword')->trim()) {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('name', 'like', "%{$keyword}%")
                    ->orWhere('name_kana', 'like', "%{$keyword}%")
                    ->orWhere('other_conditions', 'like', "%{$keyword}%");
            });
        }

        if ($agencyId = $request->integer('agency')) {
            $query->where('agency_id', $agencyId);
        }

        if ($statuses = $request->input('status', [])) {
            $statuses = array_filter((array) $statuses);
            if (!empty($statuses)) {
                $query->whereIn('status_code', $statuses);
            }
        }

        if ($introducedFrom = $request->date('introduced_from')) {
            $query->whereDate('introduced_on', '>=', $introducedFrom);
        }

        if ($introducedTo = $request->date('introduced_to')) {
            $query->whereDate('introduced_on', '<=', $introducedTo);
        }

        if ($handlerId = $request->integer('handler')) {
            $query->where(function ($inner) use ($handlerId) {
                $inner->where('handler1_user_id', $handlerId)
                    ->orWhere('handler2_user_id', $handlerId);
            });
        }

        if ($interviewFrom = $request->input('interview_from')) {
            $from = Carbon::parse($interviewFrom);
            $query->whereHas('interviews', fn ($inner) => $inner->where('scheduled_at', '>=', $from));
        }

        if ($interviewTo = $request->input('interview_to')) {
            $to = Carbon::parse($interviewTo);
            $query->whereHas('interviews', fn ($inner) => $inner->where('scheduled_at', '<=', $to));
        }

        if ($remindState = $request->string('remind_30m', 'all')) {
            if ($remindState === 'on') {
                $query->whereHas('interviews', fn ($inner) => $inner->where('remind_30m_enabled', true));
            } elseif ($remindState === 'off') {
                $query->whereHas('interviews', fn ($inner) => $inner->where('remind_30m_enabled', false));
            }
        }

        if ($user && $viewState = $request->string('view_state', 'all')) {
            if ($viewState === 'unread') {
                $query->whereDoesntHave('views', fn ($q) => $q->where('user_id', $user->id));
            } elseif ($viewState === 'read') {
                $query->whereHas('views', fn ($q) => $q->where('user_id', $user->id));
            }
        }

        $candidates = $query->orderByDesc('introduced_on')
            ->paginate(10)
            ->withQueryString();

        $agencies = Agency::query()->orderBy('name')->get();
        $statuses = CandidateStatus::query()->orderBy('sort_order')->get();
        $handlers = User::query()->where('is_active', true)->orderBy('name')->get();

        return view('candidates.index', [
            'candidates' => $candidates,
            'agencies' => $agencies,
            'statuses' => $statuses,
            'handlers' => $handlers,
            'filters' => [
                'keyword' => $request->input('keyword'),
                'agency' => $request->input('agency'),
                'status' => (array) $request->input('status', []),
                'introduced_from' => $request->input('introduced_from'),
                'introduced_to' => $request->input('introduced_to'),
                'interview_from' => $request->input('interview_from'),
                'interview_to' => $request->input('interview_to'),
                'handler' => $request->input('handler'),
                'remind_30m' => $request->input('remind_30m', 'all'),
                'view_state' => $request->input('view_state', 'all'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('candidates.create', $this->formOptions());
    }

    public function show(Request $request, Candidate $candidate)
    {
        $candidate->load([
            'agency',
            'status',
            'handler1',
            'handler2',
            'wishJob1',
            'wishJob2',
            'wishJob3',
            'interviews' => fn ($q) => $q->orderByDesc('scheduled_at'),
        ]);

        if ($user = $request->user()) {
            $view = $candidate->views()->firstOrNew(['user_id' => $user->id]);
            $now = now();

            if (!$view->exists) {
                $view->first_viewed_at = $now;
                $view->view_count = 1;
            } else {
                $view->view_count = ($view->view_count ?? 0) + 1;
            }

            $view->last_viewed_at = $now;
            $view->save();
        }

        return view('candidates.show', compact('candidate'));
    }

    public function edit(Candidate $candidate): View
    {
        $candidate->load([
            'agency',
            'status',
            'handler1',
            'handler2',
            'wishJob1',
            'wishJob2',
            'wishJob3',
        ]);

        return view('candidates.edit', array_merge(
            $this->formOptions(),
            ['candidate' => $candidate]
        ));
    }

    private function formOptions(): array
    {
        return [
            'agencies' => Agency::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'jobCategories' => JobCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'handlers' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'candidateStatuses' => CandidateStatus::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(),
        ];
    }
}
