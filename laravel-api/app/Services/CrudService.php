<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CrudRepository;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * list / store / update / delete on top of a CrudRepository.
 * Subclasses set $resource and inject their concrete repository.
 */
abstract class CrudService
{
    /** @var class-string<JsonResource> */
    protected string $resource;

    public function __construct(protected CrudRepository $repository) {}

    public function list(array $payload): JsonResource
    {
        return $this->resource::collection($this->repository->list($payload));
    }

    public function store(array $payload): int
    {
        return $this->repository->executeStore($payload);
    }

    public function update(array $payload): int
    {
        return $this->repository->executeUpdate($payload);
    }

    public function delete(array $payload): void
    {
        $this->repository->executeDelete($payload['ids'] ?? []);
    }
}
