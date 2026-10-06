<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        // Flush Redis data after each test
        if (config('database.redis.client')) {
            try {
                \Illuminate\Support\Facades\Redis::flushdb();
            } catch (\Exception $e) {
                // Ignore redis errors if connection fails, but log if needed
            }
        }

        parent::tearDown();
    }
}
