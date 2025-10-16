<?php

namespace App\Http\Controllers;

use App\Exceptions\LineworksServiceUnavailableException;
use App\Http\Requests\CandidateRequest;
use App\Http\Requests\ChangeCandidateStatusRequest;
use App\Http\Requests\UpdateCandidateMemoRequest;
use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\CandidateStatusHistory;
use App\Models\Interview;
use App\Models\JobCategory;
use App\Models\SkillSheet;
use App\Models\User;
use App\Services\LineworksCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\CandidateAssignmentMail;
use Throwable;

class CandidateController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];
    private const DEFAULT_PER_PAGE = 100;

    public function index(Request $request)
    {
        $user = $request->user();
        $remindState = (string) $request->string('remind_30m', 'all');
        $viewState = (string) $request->string('view_state', 'all');
        [$sortKey, $sortDirection] = $this->parseSort($request);
        $perPage = $this->resolvePerPage($request);

        $candidates = $this->filteredCandidatesQuery($request, $user, $sortKey, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

    $agencies = Agency::query()->orderBy('name')->get();
    $statuses = CandidateStatus::query()->orderBy('sort_order')->get();
    $handlers = User::query()->where('is_active', true)->orderBy('name')->get();
    $jobCategories = JobCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    $employedStatusCodes = CandidateStatus::employedCodes();

    $selectedAgencies = $this->normalizeIdValues($request->input('agency'));
    $selectedWishJobs = $this->normalizeIdValues($request->input('wish_job'));
    $selectedDecidedJobs = $this->normalizeIdValues($request->input('decided_job'));

        return view('candidates.index', [
            'candidates' => $candidates,
            'agencies' => $agencies,
            'statuses' => $statuses,
            'handlers' => $handlers,
            'jobCategories' => $jobCategories,
            'employedStatusCodes' => $employedStatusCodes,
            'filters' => [
                'keyword' => $request->input('keyword'),
                'keyword_logic' => (string) $request->query('keyword_logic', 'and'),
                'agency' => $selectedAgencies,
                'wish_job' => $selectedWishJobs,
                'decided_job' => $selectedDecidedJobs,
                'status' => (array) $request->input('status', []),
                'introduced_from' => $request->input('introduced_from'),
                'introduced_to' => $request->input('introduced_to'),
                'interview_from' => $request->input('interview_from'),
                'interview_to' => $request->input('interview_to'),
                'employment_start_from' => $request->input('employment_start_from'),
                'employment_start_to' => $request->input('employment_start_to'),
                'assignment_code' => $request->input('assignment_code'),
                'assignment_locker' => $request->input('assignment_locker'),
                'handler' => $request->input('handler'),
                'remind_30m' => $remindState,
                'view_state' => $viewState,
                'per_page' => $perPage,
            ],
            'currentSort' => $sortKey,
            'currentDirection' => $sortDirection,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $fileName = 'candidates_' . now()->format('Ymd_His') . '.csv';

        [$sortKey, $sortDirection] = $this->parseSort($request);

        $query = $this->filteredCandidatesQuery($request, $user, $sortKey, $sortDirection);

        return response()->streamDownload(function () use ($query, $user) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new \RuntimeException('Failed to open output stream for CSV export.');
            }

            $encode = static fn ($value) => mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
            $headers = [
                'ID',
                '氏名',
                '氏名（カナ）',
                '派遣会社',
                '第1希望職種',
                '第2希望職種',
                '第3希望職種',
                '就業する職種',
                '紹介日',
                '就業開始日時',
                '見学確定日時',
                'ステータス',
                '状態変化日',
                '対応者1',
                '対応者2',
                '閲覧状態',
                '30分前リマインド',
                'アサインコードA',
                'アサインコードB',
                '配属ロッカー',
            ];

            fputcsv($handle, array_map($encode, $headers));

            $query->chunk(200, function ($candidates) use ($handle, $user, $encode) {
                foreach ($candidates as $candidate) {
                    $confirmedInterview = $candidate->confirmedInterview;
                    $viewRecord = $user ? $candidate->views->first() : null;

                    $row = [
                        str_pad((string) $candidate->id, 6, '0', STR_PAD_LEFT),
                        $candidate->name ?? '',
                        $candidate->name_kana ?? '',
                        optional($candidate->agency)->name ?? '',
                        optional($candidate->wishJob1)->name ?? '',
                        optional($candidate->wishJob2)->name ?? '',
                        optional($candidate->wishJob3)->name ?? '',
                        optional($candidate->decidedJob)->name ?? '',
                        optional($candidate->introduced_on)->format('Y/m/d') ?? '',
                        optional($candidate->employment_start_at)->format('Y/m/d H:i') ?? '',
                        optional(optional($confirmedInterview)->scheduled_at)->format('Y/m/d H:i') ?? '',
                        optional($candidate->status)->label ?? '',
                        optional($candidate->status_changed_on)->format('Y/m/d') ?? '',
                        optional($candidate->handler1)->name ?? '',
                        optional($candidate->handler2)->name ?? '',
                        $viewRecord ? '閲覧済' : '未閲覧',
                        match ($confirmedInterview?->remind_30m_enabled) {
                            true => 'ON',
                            false => 'OFF',
                            default => '未設定',
                        },
                        $candidate->assignment_worker_code_a ?? '',
                        $candidate->assignment_worker_code_b ?? '',
                        $candidate->assignment_locker ?? '',
                    ];

                    fputcsv($handle, array_map($encode, array_map(static fn ($value) => $value ?? '', $row)));
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
        ]);
    }

    protected function filteredCandidatesQuery(Request $request, ?User $user, ?string $sort = null, ?string $direction = null, bool $withRelations = true): Builder
    {
        $query = Candidate::query();

        if ($withRelations) {
            $query->with([
                'agency',
                'status',
                'handler1',
                'handler2',
                'wishJob1',
                'wishJob2',
                'wishJob3',
                'decidedJob',
                'confirmedInterview',
                'views' => fn ($q) => $user ? $q->where('user_id', $user->id) : $q->whereRaw('1 = 0'),
            ]);
        }

        if ($user) {
            $query->withCount([
                'views as user_viewed' => fn ($q) => $q->where('user_id', $user->id),
            ]);
        }

        if ($keyword = (string) $request->string('keyword')->trim()) {
            $keyword = mb_convert_kana($keyword, 's');
            $phrases = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $logic = strtolower((string) $request->query('keyword_logic', 'and'));
            $useOr = $logic === 'or';

            if (empty($phrases)) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhere('name_kana', 'like', "%{$keyword}%")
                        ->orWhere('other_conditions', 'like', "%{$keyword}%");
                });
            } else {
                $query->where(function ($outer) use ($phrases, $useOr) {
                    foreach ($phrases as $phrase) {
                        $outer->{$useOr ? 'orWhere' : 'where'}(function ($inner) use ($phrase) {
                            $inner->where('name', 'like', "%{$phrase}%")
                                ->orWhere('name_kana', 'like', "%{$phrase}%")
                                ->orWhere('other_conditions', 'like', "%{$phrase}%");
                        });
                    }
                });
            }
        }

        $agencyIds = $this->normalizeIdValues($request->input('agency'));
        if (!empty($agencyIds)) {
            $query->whereIn('agency_id', $agencyIds);
        }

        $wishJobIds = $this->normalizeIdValues($request->input('wish_job'));
        if (!empty($wishJobIds)) {
            $query->where(function ($inner) use ($wishJobIds) {
                $inner->whereIn('wish_job1_id', $wishJobIds)
                    ->orWhereIn('wish_job2_id', $wishJobIds)
                    ->orWhereIn('wish_job3_id', $wishJobIds);
            });
        }

        $decidedJobIds = $this->normalizeIdValues($request->input('decided_job'));
        if (!empty($decidedJobIds)) {
            $query->whereIn('decided_job_category_id', $decidedJobIds);
        }

        $statuses = array_filter((array) $request->input('status', []));
        if (!empty($statuses)) {
            $query->whereIn('status_code', $statuses);
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

        if ($interviewFrom = $request->date('interview_from')) {
            $query->whereHas('confirmedInterview', fn ($inner) => $inner->whereDate('scheduled_at', '>=', $interviewFrom));
        }

        if ($interviewTo = $request->date('interview_to')) {
            $query->whereHas('confirmedInterview', fn ($inner) => $inner->whereDate('scheduled_at', '<=', $interviewTo));
        }

        if ($employmentStartFrom = $request->date('employment_start_from')) {
            $query->whereDate('employment_start_at', '>=', $employmentStartFrom);
        }

        if ($employmentStartTo = $request->date('employment_start_to')) {
            $query->whereDate('employment_start_at', '<=', $employmentStartTo);
        }

        if ($assignmentCode = (string) $request->string('assignment_code')->trim()) {
            $query->where(function ($inner) use ($assignmentCode) {
                $inner->where('assignment_worker_code_a', 'like', "%{$assignmentCode}%")
                    ->orWhere('assignment_worker_code_b', 'like', "%{$assignmentCode}%");
            });
        }

        if ($assignmentLocker = (string) $request->string('assignment_locker')->trim()) {
            $query->where('assignment_locker', 'like', "%{$assignmentLocker}%");
        }

        $remindState = (string) $request->string('remind_30m', 'all');

        if ($remindState === 'on') {
            $query->whereHas('confirmedInterview', fn ($inner) => $inner->where(function ($q) {
                $q->where('remind_30m_enabled', true)
                    ->orWhereNull('remind_30m_enabled');
            }));
        } elseif ($remindState === 'off') {
            $query->whereHas('confirmedInterview', fn ($inner) => $inner->where('remind_30m_enabled', false));
        }

        $viewState = (string) $request->string('view_state', 'all');

        if ($user) {
            if ($viewState === 'unread') {
                $query->whereDoesntHave('views', fn ($q) => $q->where('user_id', $user->id));
            } elseif ($viewState === 'read') {
                $query->whereHas('views', fn ($q) => $q->where('user_id', $user->id));
            }
        }

        $this->applySorting($query, $sort, $direction, $user);

        return $query;
    }

    protected function parseSort(Request $request): array
    {
        $sort = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('direction', ''));

        $allowed = [
            'viewed' => 'asc',
            'name' => 'asc',
            'agency' => 'asc',
            'wish_job' => 'asc',
            'decided_job' => 'asc',
            'introduced_on' => 'desc',
            'interview_at' => 'asc',
            'status' => 'asc',
            'status_changed_on' => 'desc',
        ];

        if (!array_key_exists($sort, $allowed)) {
            return [null, null];
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $allowed[$sort];
        }

        return [$sort, $direction];
    }

    protected function applySorting(Builder $query, ?string $sort, ?string $direction, ?User $user): void
    {
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : null;

        if (!$sort) {
            if ($user) {
                $query->orderBy('user_viewed');
            }

            $query->orderByDesc('introduced_on');
            $query->orderByDesc('id');

            return;
        }

        $direction = $direction ?? 'asc';

        switch ($sort) {
            case 'viewed':
                if ($user) {
                    $query->orderBy('user_viewed', $direction);
                }
                break;
            case 'name':
                $query->orderBy('name', $direction);
                break;
            case 'agency':
                $query->orderBy(
                    Agency::select('name')->whereColumn('agencies.id', 'candidates.agency_id'),
                    $direction
                );
                break;
            case 'wish_job':
                $query->orderBy(
                    JobCategory::select('name')->whereColumn('job_categories.id', 'candidates.wish_job1_id'),
                    $direction
                );
                break;
            case 'decided_job':
                $query->orderBy(
                    JobCategory::select('name')->whereColumn('job_categories.id', 'candidates.decided_job_category_id'),
                    $direction
                );
                break;
            case 'introduced_on':
                $query->orderBy('introduced_on', $direction);
                break;
            case 'interview_at':
                $query->orderBy(
                    Interview::select('scheduled_at')
                        ->whereColumn('interviews.candidate_id', 'candidates.id')
                        ->orderByDesc('scheduled_at')
                        ->limit(1),
                    $direction
                );
                break;
            case 'status':
                $query->orderBy(
                    CandidateStatus::select('label')->whereColumn('candidate_statuses.code', 'candidates.status_code'),
                    $direction
                );
                break;
            case 'status_changed_on':
                $query->orderBy('status_changed_on', $direction);
                break;
            default:
                if ($user) {
                    $query->orderBy('user_viewed');
                }
                $query->orderByDesc('introduced_on');
                break;
        }

        $query->orderByDesc('id');
    }

    public function create(): View
    {
        return view('candidates.create', array_merge(
            $this->formOptions(),
            [
                'formAction' => route('candidates.store'),
                'httpMethod' => 'POST',
                'confirmedInterview' => null,
            ]
        ));
    }

    public function store(CandidateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $shouldNotify = $request->boolean('notify_handlers');

        $candidate = DB::transaction(function () use ($request, $user) {
            $data = $request->validated();

            $candidate = Candidate::create($this->candidateAttributesFromRequest($request, $user));

            $this->recordStatusHistory($candidate, null, $candidate->status_code, $user?->id);

            $this->persistSkillSheets($candidate, $request->file('skill_sheets', []), $user?->id);

            $remindOverride = $request->has('remind_30m_enabled')
                ? $request->boolean('remind_30m_enabled')
                : true;

            $this->syncConfirmedInterview($candidate, $data, $remindOverride);

            return $candidate;
        });

        $this->markCandidateViewedBy($candidate, $user);
        $this->notifyHandlers($candidate, $shouldNotify, false, $user);

        $candidate->loadMissing('status');

        if (CandidateStatus::isEmployed((string) $candidate->status_code)) {
            $request->session()->put('celebration.payload', [
                'candidate_name' => $candidate->name,
                'status_label' => $candidate->status?->label ?? '就業決定',
                'redirect_url' => route('dashboard'),
                'delay_seconds' => 5,
            ]);

            $request->session()->flash('status', '候補者を登録しました。');

            return redirect()->route('candidates.celebrate');
        }

        return redirect()
            ->route('dashboard')
            ->with('status', '候補者を登録しました。');
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
            'decidedJob',
            'interviews' => fn ($q) => $q->orderByDesc('scheduled_at'),
            'confirmedInterview',
            'skillSheets' => fn ($q) => $q->with('uploader')->orderByDesc('created_at'),
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

        $backUrl = $request->input('back');

        if (!is_string($backUrl) || !Str::startsWith($backUrl, url('/'))) {
            $backUrl = route('candidates.index');
        }

        $lineworksService = app(LineworksCalendarService::class);
        $confirmedAt = optional($candidate->confirmedInterview)->scheduled_at;
        $lineworksConfigured = $lineworksService->isConfigured();
        $lineworksReady = $lineworksConfigured && $this->hasConfirmedInterviewDateTime($confirmedAt);

        return view('candidates.show', [
            'candidate' => $candidate,
            'backUrl' => $backUrl,
            'lineworksConfigured' => $lineworksConfigured,
            'lineworksReady' => $lineworksReady,
        ]);
    }

    public function edit(Request $request, Candidate $candidate): View
    {
        $candidate->load([
            'agency',
            'status',
            'handler1',
            'handler2',
            'wishJob1',
            'wishJob2',
            'wishJob3',
            'decidedJob',
            'skillSheets' => fn ($q) => $q->with('uploader')->orderByDesc('created_at'),
            'confirmedInterview',
        ]);

        $confirmedInterview = $candidate->confirmedInterview;
        $backUrl = $request->input('back');

        if (!is_string($backUrl) || !Str::startsWith($backUrl, url('/'))) {
            $backUrl = null;
        }

        return view('candidates.edit', array_merge(
            $this->formOptions(),
            [
                'candidate' => $candidate,
                'formAction' => route('candidates.update', $candidate),
                'httpMethod' => 'PUT',
                'confirmedInterview' => $confirmedInterview,
                'backUrl' => $backUrl,
            ]
        ));
    }

    public function update(CandidateRequest $request, Candidate $candidate): RedirectResponse
    {
        $user = $request->user();
        $shouldNotify = $request->boolean('notify_handlers');
        $originalStatus = $candidate->status_code;

        DB::transaction(function () use ($request, $candidate, $user, $originalStatus) {
            $data = $request->validated();

            $candidate->fill($this->candidateAttributesFromRequest($request, $user, $candidate));
            $candidate->save();

            if ($originalStatus !== $candidate->status_code) {
                $this->recordStatusHistory($candidate, $originalStatus, $candidate->status_code, $user?->id);
            }

            if ($request->hasFile('skill_sheets')) {
                $this->persistSkillSheets($candidate, $request->file('skill_sheets', []), $user?->id);
            }

            $remindOverride = $request->has('remind_30m_enabled')
                ? $request->boolean('remind_30m_enabled')
                : null;

            $this->syncConfirmedInterview($candidate, $data, $remindOverride);
        });

        $candidate->refresh();
        $candidate->loadMissing('status');

        $this->notifyHandlers($candidate, $shouldNotify, true, $user);

        $backUrl = $request->input('back');

        if (!is_string($backUrl) || !Str::startsWith($backUrl, url('/'))) {
            $backUrl = route('candidates.index');
        }

        if (!CandidateStatus::isEmployed((string) $originalStatus)
            && CandidateStatus::isEmployed((string) $candidate->status_code)
        ) {
            $request->session()->put('celebration.payload', [
                'candidate_name' => $candidate->name,
                'status_label' => $candidate->status?->label ?? '就業決定',
                'redirect_url' => $backUrl,
                'delay_seconds' => 5,
            ]);

            $request->session()->flash('status', '候補者情報を更新しました。');

            return redirect()->route('candidates.celebrate');
        }

        return redirect()
            ->to($backUrl)
            ->with('status', '候補者情報を更新しました。');
    }

    public function celebrate(Request $request): ViewContract|RedirectResponse
    {
        $payload = $request->session()->pull('celebration.payload');

        if (!is_array($payload)) {
            return redirect()->route('dashboard');
        }

        $request->session()->reflash();

        $delaySeconds = (int) ($payload['delay_seconds'] ?? 5);
        $delaySeconds = $delaySeconds > 0 ? $delaySeconds : 5;

        return view('candidates.celebrate', [
            'candidateName' => $payload['candidate_name'] ?? null,
            'statusLabel' => $payload['status_label'] ?? '就業決定',
            'redirectUrl' => $payload['redirect_url'] ?? route('dashboard'),
            'delaySeconds' => $delaySeconds,
        ]);
    }

    public function updateMemo(UpdateCandidateMemoRequest $request, Candidate $candidate): RedirectResponse
    {
        $data = $request->validated();

        $candidate->other_conditions = $data['other_conditions'] ?? null;
        $candidate->updated_by = $request->user()?->id;
        $candidate->save();

        $backUrl = $data['back'] ?? null;

        if (!is_string($backUrl) || !Str::startsWith($backUrl, url('/'))) {
            $backUrl = null;
        }

        $routeParams = ['candidate' => $candidate->getKey()];

        if ($backUrl) {
            $routeParams['back'] = $backUrl;
        }

        return redirect()
            ->route('candidates.show', $routeParams)
            ->with('status', 'その他条件・メモを更新しました。');
    }

    public function registerLineworks(Request $request, Candidate $candidate, LineworksCalendarService $lineworks): RedirectResponse
    {
        $candidate->loadMissing(['agency', 'handler1', 'handler2', 'confirmedInterview']);

        $backUrl = $request->input('back');
        $routeParams = ['candidate' => $candidate->getKey()];

        if (is_string($backUrl) && Str::startsWith($backUrl, url('/'))) {
            $routeParams['back'] = $backUrl;
        } else {
            $backUrl = null;
        }

        $confirmedAt = optional($candidate->confirmedInterview)->scheduled_at;

        if (!$this->hasConfirmedInterviewDateTime($confirmedAt)) {
            return redirect()->route('candidates.show', $routeParams)
                ->with('lineworks_error', '見学確定日と時間の両方を設定してください。');
        }

        if (!$lineworks->isConfigured()) {
            return redirect()->route('candidates.show', $routeParams)
                ->with('lineworks_error', 'LINE WORKS の設定が完了していません。');
        }

        try {
            $lineworks->createInterviewEvent($candidate, $confirmedAt);
        } catch (LineworksServiceUnavailableException $e) {
            Log::warning('LINE WORKS calendar registration unavailable', [
                'candidate_id' => $candidate->getKey(),
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('candidates.show', $routeParams)
                ->with('lineworks_error', 'LINE WORKSが一時的に利用できないため、カレンダー登録は完了していません。時間を置いて再度登録を実行してください。');
        } catch (Throwable $e) {
            Log::error('LINE WORKS calendar registration failed', [
                'candidate_id' => $candidate->getKey(),
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('candidates.show', $routeParams)
                ->with('lineworks_error', 'LINE WORKSカレンダーへの登録に失敗しました。');
        }

        return redirect()->route('candidates.show', $routeParams)
            ->with('lineworks_status', 'LINE WORKSカレンダーに登録しました。');
    }

    public function changeStatus(ChangeCandidateStatusRequest $request, Candidate $candidate): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $newStatus = $data['status_code'];
        $originalStatus = $candidate->status_code;
        $decidedJobId = $data['decided_job'] ?? null;
        $now = now();

        $statusChanged = DB::transaction(function () use ($candidate, $user, $newStatus, $originalStatus, $now, $decidedJobId) {
            $statusHasChanged = $originalStatus !== $newStatus;

            if ($statusHasChanged) {
                $candidate->status_code = $newStatus;
                $candidate->status_changed_on = $now;
            }

            if (CandidateStatus::isEmployed((string) $newStatus)) {
                $candidate->decided_job_category_id = $decidedJobId;
            } else {
                $candidate->decided_job_category_id = null;
            }

            $candidate->updated_by = $user?->id;
            $candidate->save();

            if ($statusHasChanged) {
                $this->recordStatusHistory($candidate, $originalStatus, $newStatus, $user?->id);
            }

            return $statusHasChanged;
        });

        $candidate->refresh()->load(['status', 'decidedJob']);

        $redirectTarget = (string) $request->input('redirect_to', route('candidates.index'));

        if (!Str::startsWith($redirectTarget, url('/'))) {
            $redirectTarget = route('candidates.index');
        }

        $shouldCelebrate = $statusChanged && CandidateStatus::isEmployed((string) $candidate->status_code);

        if ($shouldCelebrate) {
            $request->session()->put('celebration.payload', [
                'candidate_name' => $candidate->name,
                'status_label' => $candidate->status?->label ?? '就業決定',
                'redirect_url' => $redirectTarget,
                'delay_seconds' => 5,
            ]);

            $request->session()->flash('status', 'ステータスを更新しました。');
        }

        return response()->json([
            'status_code' => $candidate->status_code,
            'status_label' => $candidate->status?->label ?? 'ステータス未設定',
            'status_color' => $candidate->status?->color_code ?? '#DFE7F3',
            'status_changed_on' => optional($candidate->status_changed_on)->format('Y/m/d') ?? '—',
            'decided_job_label' => optional($candidate->decidedJob)->name ?? '—',
            'decided_job_id' => $candidate->decided_job_category_id,
            'changed' => $statusChanged,
            'celebrate_url' => $shouldCelebrate ? route('candidates.celebrate') : null,
        ]);
    }

    private function hasConfirmedInterviewDateTime(?Carbon $confirmedAt): bool
    {
        if (!$confirmedAt) {
            return false;
        }

        return !$confirmedAt->equalTo($confirmedAt->copy()->startOfDay());
    }

    private function candidateAttributesFromRequest(CandidateRequest $request, ?User $user, ?Candidate $candidate = null): array
    {
        $data = $request->validated();

        $attributes = [
            'name' => $data['name'],
            'name_kana' => $data['name_kana'],
            'agency_id' => $data['agency_id'],
            'wish_job1_id' => $data['wish_job1'] ?? null,
            'wish_job2_id' => $data['wish_job2'] ?? null,
            'wish_job3_id' => $data['wish_job3'] ?? null,
            'decided_job_category_id' => CandidateStatus::isEmployed((string) ($data['status'] ?? '')) ? ($data['decided_job'] ?? null) : null,
            'introduced_on' => $data['introduced_on'],
            'visit_candidate1_at' => $this->combineDateTime($data['visit_candidate1_date'] ?? null, $data['visit_candidate1_time'] ?? null),
            'visit_candidate2_at' => $this->combineDateTime($data['visit_candidate2_date'] ?? null, $data['visit_candidate2_time'] ?? null),
            'visit_candidate3_at' => $this->combineDateTime($data['visit_candidate3_date'] ?? null, $data['visit_candidate3_time'] ?? null),
            'handler1_user_id' => $data['handler1'],
            'handler2_user_id' => $data['handler2'] ?? null,
            'transport_cost_day' => $data['transport_day'] ?? null,
            'transport_cost_month' => $data['transport_month'] ?? null,
            'other_conditions' => $data['other_conditions'] ?? null,
            'introduction_note' => $data['introduction_note'] ?? null,
            'status_code' => $data['status'],
            'status_changed_on' => $data['status_changed_on'] ?? null,
            'employment_start_at' => $this->combineDateTime($data['employment_start_date'] ?? null, $data['employment_start_time'] ?? null),
            'assignment_worker_code_a' => $data['assignment_worker_code_a'] ?? null,
            'assignment_worker_code_b' => $data['assignment_worker_code_b'] ?? null,
            'assignment_locker' => $data['assignment_locker'] ?? null,
        ];

        if (!$candidate || !$candidate->exists) {
            $attributes['created_by'] = $user?->id;
        }

        $attributes['updated_by'] = $user?->id;

        return $attributes;
    }

    private function combineDateTime(?string $date, ?string $time): ?string
    {
        if (!$date) {
            return null;
        }

        if ($time) {
            return Carbon::parse($date . ' ' . $time);
        }

        return Carbon::parse($date)->startOfDay();
    }

    private function persistSkillSheets(Candidate $candidate, array $files, ?int $uploaderId): void
    {
        $files = array_filter($files);

        if (empty($files)) {
            return;
        }

        $existingCount = $candidate->skillSheets()->count();
        $maxFiles = 5;

        if ($existingCount + count($files) > $maxFiles) {
            throw ValidationException::withMessages([
                'skill_sheets' => 'スキルシートは既存分を含め最大5件までです。',
            ]);
        }

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME) ?: 'skillsheet');
            $timestamp = now()->format('YmdHis');
            $storedFileName = sprintf('%d_%s_%s.%s', $candidate->id, $timestamp, $baseName, $extension);

            $path = $file->storeAs("skill_sheets/{$candidate->id}", $storedFileName, 'local');

            SkillSheet::create([
                'candidate_id' => $candidate->id,
                'file_path' => $path,
                'original_name' => $originalName,
                'size_bytes' => $file->getSize(),
                'uploaded_by' => $uploaderId,
            ]);
        }
    }

    private function recordStatusHistory(Candidate $candidate, ?string $oldCode, string $newCode, ?int $userId): void
    {
        CandidateStatusHistory::create([
            'candidate_id' => $candidate->id,
            'old_code' => $oldCode,
            'new_code' => $newCode,
            'changed_by' => $userId,
            'changed_at' => now(),
        ]);
    }

    private function syncConfirmedInterview(Candidate $candidate, array $data, ?bool $remindEnabledOverride = null): void
    {
        $scheduledAt = $this->combineDateTime($data['visit_confirmed_date'] ?? null, $data['visit_confirmed_time'] ?? null);

        /** @var Interview|null $existing */
        $existing = $candidate->interviews()->orderByDesc('scheduled_at')->first();

        if ($remindEnabledOverride === null) {
            if (array_key_exists('remind_30m_enabled', $data)) {
                $remindEnabled = (bool) $data['remind_30m_enabled'];
            } elseif ($existing) {
                $remindEnabled = (bool) $existing->remind_30m_enabled;
            } else {
                $remindEnabled = true;
            }
        } else {
            $remindEnabled = $remindEnabledOverride;
        }

        if (!$scheduledAt) {
            if ($existing) {
                $existing->delete();
            }

            return;
        }

        if (!$existing) {
            $candidate->interviews()->create([
                'scheduled_at' => $scheduledAt,
                'remind_30m_enabled' => $remindEnabled,
                'remind_prev_day_sent' => false,
                'remind_1h_sent' => false,
                'remind_30m_sent' => false,
            ]);

            return;
        }

        $existing->fill([
            'scheduled_at' => $scheduledAt,
            'remind_30m_enabled' => $remindEnabled,
            'remind_prev_day_sent' => false,
            'remind_1h_sent' => false,
            'remind_30m_sent' => false,
        ])->save();
    }

    private function notifyHandlers(Candidate $candidate, bool $shouldNotify, bool $isUpdate, ?User $actor): void
    {
        if (!$shouldNotify) {
            return;
        }

        $candidate->loadMissing(['handler1', 'handler2']);

        $handlers = $candidate->handlerCollection()
            ->filter(fn ($handler) => $handler && filled($handler->email))
            ->unique(fn ($handler) => $handler->id)
            ->values();

        if ($handlers->isEmpty()) {
            return;
        }

        $candidate->loadMissing(['agency', 'status']);

        $queueName = config('queue.notification_mail_queue', 'reminders');

        foreach ($handlers as $handler) {
            Log::info('Queuing candidate assignment notification', [
                'candidate_id' => $candidate->id,
                'handler_id' => $handler->id,
                'queue' => $queueName,
                'is_update' => $isUpdate,
                'triggered_by' => $actor?->id,
            ]);

            Mail::to($handler->email)->queue(
                (new CandidateAssignmentMail($candidate, $handler, $isUpdate, $actor))
                    ->onQueue($queueName)
            );
        }
    }

    private function markCandidateViewedBy(Candidate $candidate, ?User $user): void
    {
        if (!$user) {
            return;
        }

        $view = $candidate->views()->firstOrNew(['user_id' => $user->id]);
        $now = now();

        if (!$view->exists) {
            $view->first_viewed_at = $now;
            $view->view_count = 1;
        } else {
            $view->first_viewed_at = $view->first_viewed_at ?? $now;
            $view->view_count = max(1, (int) ($view->view_count ?? 1));
        }

        $view->last_viewed_at = $now;
        $view->save();
    }

    private function normalizeIdValues(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        $items = is_array($value) ? $value : [$value];

        $ids = [];
        foreach ($items as $item) {
            if ($item === null || $item === '') {
                continue;
            }

            if (is_numeric($item)) {
                $ids[] = (int) $item;
                continue;
            }

            if (is_string($item) && ctype_digit($item)) {
                $ids[] = (int) $item;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            return self::DEFAULT_PER_PAGE;
        }

        return $perPage;
    }

    public function names(Request $request): JsonResponse
    {
        $user = $request->user();
        [$sortKey, $sortDirection] = $this->parseSort($request);

        $names = [];

        $this->filteredCandidatesQuery($request, $user, $sortKey, $sortDirection, false)
            ->chunk(500, function ($chunk) use (&$names) {
                foreach ($chunk as $candidate) {
                    $name = (string) ($candidate->name ?? '');

                    if ($name !== '') {
                        $names[] = $name;
                    }
                }
            });

        return response()->json([
            'names' => $names,
        ]);
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
            'employedStatusCodes' => CandidateStatus::employedCodes(),
        ];
    }
}
