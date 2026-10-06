<?php

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use App\Enums\TypeOfMethod;
use App\Exceptions\Auth\LoginFailedException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionMiddleware
{
    /**
     * List of exceptions that should trigger a COMMIT instead of ROLLBACK.
     */
    protected array $exceptionsShouldCommit = [
        LoginFailedException::class,
    ];

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

            if ($this->isErrorResponse($response)) {
                $exception = $response->exception ?? null;
                $this->finishTransaction($this->shouldCommit($exception));

                return $response;
            }

            $this->finishTransaction(true);

            return $response;
        } catch (Throwable $e) {
            $this->finishTransaction($this->shouldCommit($e));
            throw $e;
        }
    }

    /**
     * Determine if the request is a write operation.
     */
    protected function isWriteOperation(Request $request): bool
    {
        $writeMethods = [
            TypeOfMethod::POST->label(),
            TypeOfMethod::PUT->label(),
            TypeOfMethod::PATCH->label(),
            TypeOfMethod::DELETE->label(),
        ];

        return in_array($request->method(), $writeMethods);
    }

    /**
     * Determine if the response indicates an error (status >= 400).
     */
    protected function isErrorResponse($response): bool
    {
        return method_exists($response, 'getStatusCode')
          && $response->getStatusCode() >= CommonVal::HTTP_BAD_REQUEST;
    }

    /**
     * Determine if we should commit the transaction based on the exception.
     * Returns true if exception matches allowlist, or if no exception (success).
     */
    protected function shouldCommit(?Throwable $e): bool
    {
        if (! $e) {
            return false; // Default rollback for error response without specific allowed exception
        }

        foreach ($this->exceptionsShouldCommit as $allowedClass) {
            if ($e instanceof $allowedClass) {
                return true;
            }
        }

        return false;
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
