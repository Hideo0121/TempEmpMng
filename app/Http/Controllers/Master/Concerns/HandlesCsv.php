<?php

namespace App\Http\Controllers\Master\Concerns;

trait HandlesCsv
{
    protected function normalizeBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $value = strtolower((string) $value);

        return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    protected function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
