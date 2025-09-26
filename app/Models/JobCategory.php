<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class JobCategory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
