<?php

declare(strict_types=1);

namespace App\Jobs\System;

use App\Constants\BackgroundTaskConst;
use App\Jobs\BackgroundJob;
use RuntimeException;

/**
 * Checks the whole chain (queue → worker → Reverb → browser) from the screen: waits a few seconds,
 * then finishes or fails on purpose.
 */
final class TestBackgroundJob extends BackgroundJob
{
    public int $tries = 1;

    public function __construct(int $adminId, public readonly int $seconds, public readonly bool $fail)
    {
        parent::__construct($adminId);
    }

    public function type(): string
    {
        return BackgroundTaskConst::TYPE_TEST;
    }

    public function run(): string
    {
        sleep($this->seconds);
        if ($this->fail) {
            throw new RuntimeException('Test background job failed on purpose');
        }

        return "Test background job finished after {$this->seconds}s";
    }
}
