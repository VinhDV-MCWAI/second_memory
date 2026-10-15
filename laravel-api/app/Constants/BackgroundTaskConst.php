<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Background jobs and live notifications (ADR-0014).
 */
final class BackgroundTaskConst
{
    /** Private channel of one admin: `admin.{id}` (the browser subscribes to `private-admin.{id}`). */
    public const CHANNEL_PREFIX = 'admin.';

    /** Event name the browser listens to (with a leading dot in laravel-echo). */
    public const FINISHED_EVENT = 'background-task.finished';

    /** How long a task's status can be read back, for a screen that missed the event. */
    public const STATUS_TTL_SECONDS = 86400;

    public const CACHE_PREFIX = 'background-task:';

    /** Task type of the test job (the "Test background job" button). */
    public const TYPE_TEST = 'system.test';

    public const TEST_MAX_SECONDS = 30;
}
