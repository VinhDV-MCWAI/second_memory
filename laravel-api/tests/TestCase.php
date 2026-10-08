<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Requests look like they come from the admin SPA, so Sanctum starts a session (ADR-0004)
        $this->withServerVariables(['HTTP_REFERER' => config('app.url')]);
    }

    /**
     * Each request starts without a resolved user, as it does under PHP-FPM; otherwise the
     * guards keep the user of the previous request and a revoked session still looks valid.
     *
     * {@inheritDoc}
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

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
