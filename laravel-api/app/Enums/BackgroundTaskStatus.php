<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Life of a background task (ADR-0014). Only COMPLETED and FAILED are broadcast.
 */
enum BackgroundTaskStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'queued',
            self::RUNNING => 'running',
            self::COMPLETED => 'completed',
            self::FAILED => 'failed',
        };
    }

    public function isFinished(): bool
    {
        return $this === self::COMPLETED || $this === self::FAILED;
    }
}
