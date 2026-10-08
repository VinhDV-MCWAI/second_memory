<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Constants\CommonVal;
use Illuminate\Routing\Route;
use Illuminate\Testing\TestResponse;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contract test (ADR-0008, P3-09): every API response a feature test receives must match the
 * schema openapi.json documents for that route, method and status code. OpenAPI 3.1 schemas are
 * JSON Schema 2020-12, so the document is loaded as-is and responses are validated by JSON pointer.
 */
final class OpenApiContract
{
    private const DOCUMENT_ID = 'https://second-memory.test/openapi.json';

    private const MEDIA_TYPE = 'application/json';

    private static ?self $instance = null;

    private readonly Validator $validator;

    /** @var array<string, mixed> */
    private readonly array $document;

    private function __construct()
    {
        $json = (string) file_get_contents(base_path('openapi.json'));
        $this->document = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $this->validator = new Validator;
        $this->validator->resolver()?->registerRaw($json, self::DOCUMENT_ID);
    }

    public static function instance(): self
    {
        return self::$instance ??= new self;
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    public function assertMatches(TestResponse $response, string $method): void
    {
        $route = $response->baseRequest->route();
        if (! $route instanceof Route || ! str_starts_with($route->uri(), 'api/')) {
            return;
        }

        $path = '/'.substr($route->uri(), strlen('api/'));
        $method = strtolower($method);
        $status = (string) $response->getStatusCode();
        $where = strtoupper($method)." {$path} → {$status}";

        if (! isset($this->document['paths'][$path][$method])) {
            return; // Not in the spec (excluded in config/scramble.php, e.g. broadcasting)
        }
        $responses = $this->document['paths'][$path][$method]['responses'];
        Assert::assertArrayHasKey($status, $responses, "{$where} is not documented in openapi.json");

        if ($response->getStatusCode() === CommonVal::HTTP_NO_CONTENT) {
            Assert::assertSame('', $response->getContent(), "{$where} must have no body");

            return;
        }

        $pointer = $this->schemaPointer($responses[$status], ['paths', $path, $method, 'responses', $status]);
        if ($pointer === null) {
            return; // Documented without a JSON body (e.g. 204)
        }

        $error = $this->validator->uriValidation(
            json_decode((string) $response->getContent(), false),
            self::DOCUMENT_ID.'#'.$pointer,
        );
        if ($error !== null) {
            Assert::fail("{$where} does not match openapi.json:\n"
                .json_encode((new ErrorFormatter)->format($error), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * JSON pointer of the response body schema, following a `$ref` to components/responses.
     *
     * @param  array<string, mixed>  $responseObject
     * @param  list<string>  $location
     */
    private function schemaPointer(array $responseObject, array $location): ?string
    {
        if (isset($responseObject['$ref'])) {
            $location = explode('/', ltrim(substr($responseObject['$ref'], 1), '/'));
            $responseObject = $this->document['components']['responses'][end($location)];
        }
        if (! isset($responseObject['content'][self::MEDIA_TYPE]['schema'])) {
            return null;
        }

        $segments = [...$location, 'content', self::MEDIA_TYPE, 'schema'];

        return '/'.implode('/', array_map(fn (string $segment): string => rawurlencode(str_replace(['~', '/'], ['~0', '~1'], $segment)), $segments));
    }
}
