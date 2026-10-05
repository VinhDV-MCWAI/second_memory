<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Master\AdminMst;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

final class ExceptionHandlerTest extends TestCase
{
    private const string BASE_URL = '/_test/exceptions';

    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix(self::BASE_URL)->group(function (): void {
            Route::get('unauthenticated', fn () => throw new AuthorizationException(Messages::E0401, CommonVal::HTTP_UNAUTHORIZED));
            Route::get('forbidden', fn () => throw new AuthorizationException);
            Route::get('model-not-found', fn () => AdminMst::query()->findOrFail(-1));
            Route::get('abort-forbidden', fn () => abort(CommonVal::HTTP_FORBIDDEN));
            Route::get('internal', fn () => throw new RuntimeException('SQLSTATE secret detail'));
        });
    }

    public function test_authorization_exception_with_explicit_code_returns_401(): void
    {
        $this->getJson(self::BASE_URL.'/unauthenticated')
            ->assertStatus(CommonVal::HTTP_UNAUTHORIZED)
            ->assertExactJson($this->envelope(CommonVal::HTTP_UNAUTHORIZED, Messages::E0401));
    }

    public function test_authorization_exception_without_code_returns_403(): void
    {
        $this->getJson(self::BASE_URL.'/forbidden')
            ->assertStatus(CommonVal::HTTP_FORBIDDEN)
            ->assertJsonPath('error.code', CommonVal::HTTP_FORBIDDEN);
    }

    public function test_http_exception_keeps_its_status_code(): void
    {
        $this->getJson(self::BASE_URL.'/abort-forbidden')
            ->assertStatus(CommonVal::HTTP_FORBIDDEN)
            ->assertJsonPath('error.code', CommonVal::HTTP_FORBIDDEN);
    }

    public function test_model_not_found_hides_model_class(): void
    {
        $this->getJson(self::BASE_URL.'/model-not-found')
            ->assertStatus(CommonVal::HTTP_NOT_FOUND)
            ->assertExactJson($this->envelope(CommonVal::HTTP_NOT_FOUND, Messages::E0404));
    }

    public function test_internal_error_hides_message_outside_debug(): void
    {
        config(['app.debug' => false]);

        $this->getJson(self::BASE_URL.'/internal')
            ->assertStatus(CommonVal::HTTP_INTERNAL_SERVER_ERROR)
            ->assertExactJson($this->envelope(CommonVal::HTTP_INTERNAL_SERVER_ERROR, Messages::E0500));
    }

    /**
     * @return array<string, mixed>
     */
    private function envelope(int $code, string $message): array
    {
        return [
            'data' => null,
            'error' => ['status' => true, 'code' => $code, 'messages' => $message],
        ];
    }
}
