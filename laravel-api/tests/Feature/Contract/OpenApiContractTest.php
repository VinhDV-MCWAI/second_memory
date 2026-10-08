<?php

declare(strict_types=1);

namespace Tests\Feature\Contract;

use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Support\OpenApiContract;
use Tests\TestCase;

/**
 * The contract check itself (P3-09): TestCase::call() runs it on every response, so these tests
 * only prove that it rejects a body or status the spec does not describe.
 */
final class OpenApiContractTest extends TestCase
{
    use AuthenticatesAdmins;

    public function test_a_body_that_drifts_from_the_spec_is_rejected(): void
    {
        Tag::factory()->create();
        $response = $this->call('GET', '/api/admin/tag/list', [], $this->loginAsOwner(AdminMst::factory()->create()));
        $body = $response->json();
        $body['data']['data'][0]['id'] = 'not-an-integer';
        $response->baseResponse->setContent((string) json_encode($body));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('#GET /admin/tag/list → 200 does not match openapi.json#');
        OpenApiContract::instance()->assertMatches($response, 'GET');
    }

    public function test_an_undocumented_status_is_rejected(): void
    {
        $response = $this->call('GET', '/api/public/skills');
        $response->baseResponse->setStatusCode(418);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('#GET /public/skills → 418 is not documented#');
        OpenApiContract::instance()->assertMatches($response, 'GET');
    }
}
