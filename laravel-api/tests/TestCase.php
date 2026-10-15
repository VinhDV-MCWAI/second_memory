<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;
use Tests\Support\OpenApiContract;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Requests look like they come from the admin SPA, so Sanctum starts a session (ADR-0004)
        $this->withServerVariables(['HTTP_REFERER' => config('app.url')]);
        // ffprobe / ffmpeg (MEDIA-11) never run in tests: they print nothing unless a test fakes an output
        Process::fake();
    }

    /**
     * Each request starts without a resolved user and without the previous request's session data,
     * as it does under PHP-FPM; otherwise the guards (and the shared session store, which merges what
     * it reads into what it holds) keep the previous request's admin and a revoked session still looks valid.
     * Every response is also checked against openapi.json (contract test, P3-09).
     *
     * {@inheritDoc}
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->driver()->flush();

        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        OpenApiContract::instance()->assertMatches($response, $method);

        return $response;
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
