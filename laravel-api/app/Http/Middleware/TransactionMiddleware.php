<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionMiddleware
{
    /**
     * Handle an incoming request with database transaction.
     *
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->isWriteOperation($request)) {
            return $next($request);
        }

        DB::beginTransaction();

        try {
            $response = $next($request);

            $this->finishTransaction(! $this->isErrorResponse($response));

            return $response;
        } catch (Throwable $e) {
            $this->finishTransaction(false);
            throw $e;
        }
    }

    /**
     * Determine if the request is a write operation.
     */
    protected function isWriteOperation(Request $request): bool
    {
        return ! $request->isMethodSafe();
    }

    /**
     * Determine if the response indicates an error (status >= 400).
     */
    protected function isErrorResponse(mixed $response): bool
    {
        return method_exists($response, 'getStatusCode')
          && $response->getStatusCode() >= CommonVal::HTTP_BAD_REQUEST;
    }

    /**
     * Commit or Rollback the transaction.
     */
    protected function finishTransaction(bool $commit): void
    {
        if ($commit) {
            DB::commit();
        } else {
            DB::rollBack();
        }
    }
}
