<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentInfo extends Model
{
    protected $table = 'recruitment_info';

    protected $guarded = [];

    protected $casts = [
        'planned_hires' => 'integer',
    ];

    public function jobCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }
}
