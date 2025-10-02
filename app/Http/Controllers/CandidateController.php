<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateRequest;
use App\Http\Requests\ChangeCandidateStatusRequest;
use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateStatus;
use App\Models\CandidateStatusHistory;
use App\Models\Interview;
use App\Models\JobCategory;
use App\Models\SkillSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\CandidateAssignmentMail;

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

        return view('candidates.index', [
            'candidates' => $candidates,
            'agencies' => $agencies,
            'statuses' => $statuses,
            'handlers' => $handlers,
            'jobCategories' => $jobCategories,
            'employedStatusCodes' => $employedStatusCodes,
            'filters' => [
                'keyword' => $request->input('keyword'),
                'agency' => $request->input('agency'),
                'wish_job' => $request->input('wish_job'),
                'decided_job' => $request->input('decided_job'),
                'status' => (array) $request->input('status', []),
                'introduced_from' => $request->input('introduced_from'),
                'introduced_to' => $request->input('introduced_to'),
                'interview_from' => $request->input('interview_from'),
                'interview_to' => $request->input('interview_to'),
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
                '見学確定日時',
                'ステータス',
                '状態変化日',
                '対応者1',
                '対応者2',
                '閲覧状態',
                '30分前リマインド',
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
            $query->where(function ($inner) use ($keyword) {
                $inner->where('name', 'like', "%{$keyword}%")
                    ->orWhere('name_kana', 'like', "%{$keyword}%")
                    ->orWhere('other_conditions', 'like', "%{$keyword}%");
            });
        }

        if ($agencyId = $request->integer('agency')) {
            $query->where('agency_id', $agencyId);
        }

        if ($wishJobId = $request->integer('wish_job')) {
            $query->where(function ($inner) use ($wishJobId) {
                $inner->where('wish_job1_id', $wishJobId)
                    ->orWhere('wish_job2_id', $wishJobId)
                    ->orWhere('wish_job3_id', $wishJobId);
            });
        }

        if ($decidedJobId = $request->integer('decided_job')) {
            $query->where('decided_job_category_id', $decidedJobId);
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

        return view('candidates.show', compact('candidate', 'backUrl'));
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

        DB::transaction(function () use ($request, $candidate, $user) {
            $data = $request->validated();
            $originalStatus = $candidate->status_code;

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

        $this->notifyHandlers($candidate, $shouldNotify, true, $user);

        $backUrl = $request->input('back');

        if (!is_string($backUrl) || !Str::startsWith($backUrl, url('/'))) {
            $backUrl = route('candidates.index');
        }

        return redirect()
            ->to($backUrl)
            ->with('status', '候補者情報を更新しました。');
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

        return response()->json([
            'status_code' => $candidate->status_code,
            'status_label' => $candidate->status?->label ?? 'ステータス未設定',
            'status_color' => $candidate->status?->color_code ?? '#DFE7F3',
            'status_changed_on' => optional($candidate->status_changed_on)->format('Y/m/d') ?? '—',
            'decided_job_label' => optional($candidate->decidedJob)->name ?? '—',
            'decided_job_id' => $candidate->decided_job_category_id,
            'changed' => $statusChanged,
        ]);
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
