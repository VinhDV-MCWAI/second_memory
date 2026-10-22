<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The envelope middleware rebuilds the response; copying the inner response's headers must replace
 * the rebuilt one's, or `Date` and `Cache-Control` go out twice (nginx logs a warning per request).
 */
final class ResponseHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_enveloped_response_sends_each_header_once(): void
    {
        $response = $this->getJson('/api/public/home')->assertOk();

        $this->assertCount(1, $response->headers->all('date'));
        $this->assertCount(1, $response->headers->all('cache-control'));
    }
}
