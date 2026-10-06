<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        // Flush Redis data after each test
        if (config('database.redis.client')) {
            try {
                Redis::flushdb();
            } catch (\Exception) {
                // Ignore redis errors if connection fails, but log if needed
            }
        }

        parent::tearDown();
    }
}
