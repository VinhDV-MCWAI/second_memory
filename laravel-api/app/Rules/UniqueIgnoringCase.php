<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Unique like Rule::unique, but "PostgreSQL" and "postgresql" count as the same value.
 * Matches the unique lower(column) indexes of the Skill Ledger tables.
 */
final class UniqueIgnoringCase implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column = 'name',
        private readonly int|string|null $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table($this->table)
            ->whereRaw("lower({$this->column}) = lower(?)", [(string) $value])
            ->when($this->ignoreId !== null, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('validation.unique')->translate();
        }
    }
}
