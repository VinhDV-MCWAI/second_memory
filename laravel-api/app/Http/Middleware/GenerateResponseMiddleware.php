<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\CommonVal;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class GenerateResponseMiddleware
{
    use ApiResponse;

    /**
     * Standardize API responses across the application.
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        // Ensure the request expects a JSON response
        $request->headers->set('Accept', 'application/json');

        // Process the request
        $response = $next($request);

        // Get the status code from the response or default to 200 OK
        $statusCode = method_exists($response, 'getStatusCode')
          ? $response->getStatusCode()
          : Response::HTTP_OK;

        if ($statusCode >= CommonVal::HTTP_BAD_REQUEST) {
            return $response;
        }

        // Process different response types
        $responseApi = $this->processResponse($response, $request, $statusCode);

        foreach ($response->headers->getCookies() as $cookie) {
            $responseApi->headers->setCookie($cookie);
        }

        // Copy non-cookie headers
        foreach ($response->headers->all() as $key => $values) {
            if (strtolower($key) !== 'set-cookie') {
                foreach ($values as $v) {
                    $responseApi->headers->set($key, $v, false);
                }
            }
        }

        return $responseApi;
    }

    /**
     * Process the response based on its type.
     */
    private function processResponse(mixed $response, Request $request, int $statusCode): JsonResponse
    {
        if ($response instanceof JsonResource) { // Handle JsonResource responses (Laravel API Resources)
            $response = $response->resolve($request);
        } elseif ($response instanceof JsonResponse) { // Handle existing JsonResponse objects
            // We only extract the data, not cookies or headers
            $response = $response->getData(true);
        } elseif (method_exists($response, 'getContent') && $response->getContent() === '') { // A void action: no data
            $response = null;
        } elseif (method_exists($response, 'getContent')) { // Handle responses with getContent method (like regular Response objects)
            // Try to decode content as JSON
            $decoded = json_decode($response->getContent(), true);

            // Return decoded content if it's valid JSON, otherwise return as is
            $response = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $response->getContent();
        }

        // Check if '_cookies' key exists in the response array
        $cookies = [];
        if (is_array($response) && array_key_exists('_cookies', $response)) {
            $cookies = $response['_cookies'];
            unset($response['_cookies']);
        }

        $responseApi = self::successResponse($response, $statusCode);

        // Set multiple cookies if they exist
        if (! empty($cookies) && is_array($cookies)) {
            foreach ($cookies as $cookieData) {
                if (is_array($cookieData)) {

                    $cookie = cookie(
                        $cookieData['name'],
                        $cookieData['value'],
                        $cookieData['minutes'],
                        $cookieData['path'] ?? null,
                        $cookieData['domain'] ?? null,
                        $cookieData['secure'] ?? null,
                        $cookieData['httpOnly'] ?? true,
                        false,
                        $cookieData['sameSite'] ?? null
                    );
                    $responseApi = $responseApi->withCookie($cookie);
                } elseif ($cookieData instanceof Cookie) {
                    $responseApi = $responseApi->withCookie($cookieData);
                }
            }
        }

        return $responseApi;
    }
}
