<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateStatus extends Model
{
    public const CODE_EMPLOYED = 'ST03';

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_employed_state' => 'boolean',
    ];

    protected static ?array $employedCodesCache = null;

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'status_code', 'code');
    }

    public static function employedCodes(): array
    {
        if (static::$employedCodesCache === null) {
            static::$employedCodesCache = static::query()
                ->where('is_employed_state', true)
                ->pluck('code')
                ->map(fn ($code) => mb_strtolower($code))
                ->all();
        }

        return static::$employedCodesCache;
    }

    public static function refreshEmployedCache(): void
    {
        static::$employedCodesCache = null;
    }

    public static function isEmployed(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        return in_array(mb_strtolower($code), static::employedCodes(), true);
    }

    public static function firstEmployedCode(): ?string
    {
        return static::query()
            ->where('is_employed_state', true)
            ->orderBy('sort_order')
            ->value('code');
    }
}
