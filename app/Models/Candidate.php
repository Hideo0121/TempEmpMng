<?php

namespace App\Models;

use App\Models\CandidateStatusHistory;
use App\Models\JobCategory;
use App\Models\SkillSheet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Candidate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'introduced_on' => 'date',
        'status_changed_on' => 'date',
        'visit_candidate1_at' => 'datetime',
        'visit_candidate2_at' => 'datetime',
        'visit_candidate3_at' => 'datetime',
        'start_on' => 'date',
        'employment_start_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function handler1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler1_user_id');
    }

    public function handler2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler2_user_id');
    }

    public function wishJob1(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'wish_job1_id');
    }

    public function wishJob2(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'wish_job2_id');
    }

    public function wishJob3(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'wish_job3_id');
    }

    public function decidedJob(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'decided_job_category_id');
    }

    public function confirmedInterview(): HasOne
    {
        return $this->hasOne(Interview::class)->latestOfMany('scheduled_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(CandidateStatus::class, 'status_code', 'code');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(CandidateView::class);
    }

    public function skillSheets(): HasMany
    {
        return $this->hasMany(SkillSheet::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CandidateStatusHistory::class);
    }

    public function handlerCollection(): Collection
    {
        return collect([$this->handler1, $this->handler2])->filter()->values();
    }
}
