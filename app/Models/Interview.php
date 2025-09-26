<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'remind_prev_day_sent' => 'boolean',
        'remind_1h_sent' => 'boolean',
        'remind_30m_sent' => 'boolean',
        'remind_30m_enabled' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
