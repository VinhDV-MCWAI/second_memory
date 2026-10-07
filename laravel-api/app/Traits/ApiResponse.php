<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Render response api
     */
    public static function renderResponse(mixed $data, array $error): JsonResponse
    {
        [$status, $code, $messages] = $error;

        return response()->json([
            'data' => $data,
            'error' => [
                'status' => $status,
                'code' => $code,
                'messages' => $messages,
            ],
        ], $code);
    }

    /**
     * Success response
     */
    public static function successResponse(mixed $data, int $code = 200): JsonResponse
    {
        return self::renderResponse($data, [false, $code, null]);
    }

    /**
     * Error response
     */
    public static function errorResponse(mixed $message, int $code): JsonResponse
    {
        return self::renderResponse(null, [true, $code, $message]);
    }
}
