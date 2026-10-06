<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasHistory
{
    /**
     * Get the history records for the model.
     * This method should be overridden in the model to specify the correct history model.
     */
    abstract public function history(): HasMany;

    /**
     * Get the latest history record.
     *
     * @return mixed
     */
    public function latestHistory()
    {
        return $this->history()->latest('created_at')->first();
    }

    /**
     * Get history records by action type.
     *
     * @return mixed
     */
    public function historyByAction(int $actionType)
    {
        return $this->history()->where('action', $actionType)->get();
    }
}
