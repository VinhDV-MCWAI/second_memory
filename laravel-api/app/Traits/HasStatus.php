<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasStatus
{
    /**
     * Scope a query to only include active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive records.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus(Builder $query, int $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Check if the model is active.
     */
    public function isActive(): bool
    {
        return $this->is_active == true;
    }

    /**
     * Activate the model.
     */
    public function activate(): bool
    {
        $this->is_active = true;

        return $this->save();
    }

    /**
     * Deactivate the model.
     */
    public function deactivate(): bool
    {
        $this->is_active = false;

        return $this->save();
    }
}
