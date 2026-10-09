<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards PRB-002: container env vars must not send the suite to the dev database.
 */
final class TestEnvironmentTest extends TestCase
{
    public function test_the_suite_runs_in_the_testing_environment(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());
    }
}
