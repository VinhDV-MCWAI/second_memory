<?php

namespace App\Traits;

use App\Enums\IsDelete;
use Illuminate\Database\Eloquent\Builder;

trait HasSoftDelete
{
    /**
     * Scope a query to only include non-deleted records.
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable().'.is_delete', IsDelete::FALSE->value);
    }

    /**
     * Scope a query to only include deleted records.
     */
    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable().'.is_delete', IsDelete::TRUE->value);
    }

    /**
     * Check if the model is soft deleted.
     */
    public function isDeleted(): bool
    {
        return $this->is_delete == IsDelete::TRUE->value;
    }

    /**
     * Soft delete the model.
     */
    public function softDelete(): bool
    {
        $this->is_delete = IsDelete::TRUE->value;

        return $this->save();
    }

    /**
     * Restore a soft deleted model.
     */
    public function restore(): bool
    {
        $this->is_delete = IsDelete::FALSE->value;

        return $this->save();
    }
}
