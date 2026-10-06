<?php

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BroadcastingAuthMiddleware;
use App\Http\Middleware\GenerateResponseMiddleware;
use App\Http\Middleware\TransactionMiddleware;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api.response' => GenerateResponseMiddleware::class,
            'db.transaction' => TransactionMiddleware::class,
            'auth.admin' => AdminMiddleware::class,
            'auth.broadcasting' => BroadcastingAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Reporting (logging) is handled by Laravel's default reporter; this callback only renders.
        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            // Laravel wraps these in HTTP exceptions before render callbacks run, dropping the
            // explicit status code (auth middleware uses 401) — match on the original instead.
            $previous = $e->getPrevious();
            if ($previous instanceof AuthorizationException || $previous instanceof ModelNotFoundException) {
                $e = $previous;
            }

            $respond = static fn (mixed $message, int $status): JsonResponse => (new class
            {
                use ApiResponse;
            })::errorResponse($message, $status);

            // An explicit HTTP status passed as the exception code wins (e.g. auth middleware uses 401).
            $explicitStatus = static function (Throwable $e): ?int {
                $code = $e->getCode();

                return is_numeric($code) && (int) $code >= 400 && (int) $code <= 599 ? (int) $code : null;
            };

            $statusOr = static fn (int $default): int => $explicitStatus($e) ?? $default;

            return match (true) {
                $e instanceof ValidationException => $respond($e->errors(), $e->status),

                $e instanceof AuthenticationException,
                $e instanceof TokenMismatchException => $respond(
                    $e->getMessage() ?: Messages::E0401,
                    $statusOr(CommonVal::HTTP_UNAUTHORIZED)
                ),

                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => $respond(
                    $e->getMessage() ?: Messages::E0403,
                    $statusOr(CommonVal::HTTP_FORBIDDEN)
                ),

                $e instanceof ThrottleRequestsException => $respond(
                    $e->getMessage() ?: Messages::E0429,
                    CommonVal::HTTP_TOO_MANY_REQUESTS
                ),

                // findOrFail() messages expose model class names; keep only custom messages.
                $e instanceof ModelNotFoundException => $respond(
                    str_starts_with($e->getMessage(), 'No query results') ? Messages::E0404 : $e->getMessage(),
                    $statusOr(CommonVal::HTTP_NOT_FOUND)
                ),

                $e instanceof HttpExceptionInterface => $respond(
                    $e->getMessage() ?: (Response::$statusTexts[$e->getStatusCode()] ?? Messages::E0500),
                    $statusOr($e->getStatusCode())
                ),

                // Business rule violations are thrown as LogicException with a user-facing message.
                $e instanceof LogicException => $respond(
                    $e->getMessage() ?: Messages::E0500,
                    $statusOr(CommonVal::HTTP_INTERNAL_SERVER_ERROR)
                ),

                // Anything else is internal: never leak SQL, file paths or stack details outside debug mode.
                default => $respond(
                    config('app.debug') && $e->getMessage() !== '' ? $e->getMessage() : Messages::E0500,
                    CommonVal::HTTP_INTERNAL_SERVER_ERROR
                ),
            };
        });
    })->create();
