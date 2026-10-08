<?php

declare(strict_types=1);

namespace App\OpenApi;

use App\Constants\CommonVal;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\NullType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Scramble documents what a controller returns; the `api.response` middleware and the exception
 * handler then wrap it as `{ data, error: { status, code, messages } }`. This transformer makes
 * openapi.json describe the body clients really get (ADR-0008, P3-09) and adds the error codes
 * Scramble cannot see: 403 on admin routes (AdminMiddleware), 404 for `{param}` routes, 429 for throttled routes.
 */
final class ResponseEnvelope
{
    private const MEDIA_TYPE = 'application/json';

    private const ERROR_SCHEMA = 'ApiError';

    public function __invoke(OpenApi $openApi): void
    {
        $components = $openApi->components;
        $errorRef = $components->addSchema(self::ERROR_SCHEMA, Schema::fromType($this->errorEnvelope()));

        // Scramble's shared 401 / 422 / 429 responses describe Laravel's default `{message}` body
        foreach ($components->responses as $response) {
            $response->setContent(self::MEDIA_TYPE, Schema::fromType($errorRef));
        }

        $routes = $this->envelopedRoutes();
        foreach ($openApi->paths as $path) {
            foreach ($path->operations as $operation) {
                $route = $routes[strtoupper($operation->method).' '.ltrim($path->path, '/')] ?? null;
                if ($route !== null) {
                    $this->wrap($operation, $route, $errorRef);
                }
            }
        }
    }

    private function wrap(Operation $operation, Route $route, Reference $errorRef): void
    {
        foreach ($operation->responses ?? [] as $response) {
            $schema = $response instanceof Response ? $response->getContent(self::MEDIA_TYPE) : null;
            if (! $schema instanceof Schema) {
                continue;
            }
            $response->setContent(self::MEDIA_TYPE, Schema::fromType((int) $response->code >= CommonVal::HTTP_BAD_REQUEST
                ? $errorRef
                : $this->successEnvelope($this->returnsVoid($route) ? new NullType : $schema->type)));
        }

        $middleware = $route->gatherMiddleware();
        $missing = array_filter([
            // Writes need the owner role; any admin route also refuses an API token without the route's ability
            CommonVal::HTTP_FORBIDDEN => array_filter($middleware, fn (string $name): bool => str_starts_with($name, 'auth.admin')) !== [],
            CommonVal::HTTP_NOT_FOUND => $route->parameterNames() !== [],
            CommonVal::HTTP_TOO_MANY_REQUESTS => array_filter($middleware, fn (string $name): bool => str_starts_with($name, 'throttle')) !== [],
        ]);
        $documented = array_map(
            fn (Response|Reference $response): string => (string) ($response instanceof Reference ? $response->resolve()->code : $response->code),
            $operation->responses ?? [],
        );
        foreach (array_keys($missing) as $code) {
            if (! in_array((string) $code, $documented, true)) {
                $operation->addResponse(Response::make($code)
                    ->setDescription(HttpResponse::$statusTexts[$code])
                    ->setContent(self::MEDIA_TYPE, Schema::fromType($errorRef)));
            }
        }
    }

    /**
     * Routes whose responses go through the envelope, keyed like "GET api/admin/skill/list" without the `api/` prefix.
     *
     * @return array<string, Route>
     */
    private function envelopedRoutes(): array
    {
        $routes = [];
        foreach (Router::getRoutes()->getRoutes() as $route) {
            if (in_array('api.response', $route->gatherMiddleware(), true)) {
                foreach ($route->methods() as $method) {
                    $routes[$method.' '.preg_replace('#^api/#', '', $route->uri())] = $route;
                }
            }
        }

        return $routes;
    }

    /**
     * Scramble documents a `void` action as an empty object; the envelope sends `data: null`.
     */
    private function returnsVoid(Route $route): bool
    {
        $action = $route->getActionName();
        if (! str_contains($action, '@')) {
            return false;
        }
        [$class, $method] = explode('@', $action);
        $returnType = (new ReflectionMethod($class, $method))->getReturnType();

        return $returnType instanceof ReflectionNamedType && $returnType->getName() === 'void';
    }

    private function successEnvelope(Type $data): ObjectType
    {
        return (new ObjectType)
            ->addProperty('data', $data)
            ->addProperty('error', (new ObjectType)
                ->addProperty('status', new BooleanType)
                ->addProperty('code', new IntegerType)
                ->addProperty('messages', new NullType)
                ->setRequired(['status', 'code', 'messages']))
            ->setRequired(['data', 'error']);
    }

    /**
     * `messages` is a string, or field => messages for validation errors (422).
     */
    private function errorEnvelope(): ObjectType
    {
        $fieldErrors = (new ObjectType)->additionalProperties((new ArrayType)->setItems(new StringType));

        return (new ObjectType)
            ->addProperty('data', new NullType)
            ->addProperty('error', (new ObjectType)
                ->addProperty('status', new BooleanType)
                ->addProperty('code', new IntegerType)
                ->addProperty('messages', (new AnyOf)->setItems([new StringType, $fieldErrors]))
                ->setRequired(['status', 'code', 'messages']))
            ->setRequired(['data', 'error']);
    }
}
