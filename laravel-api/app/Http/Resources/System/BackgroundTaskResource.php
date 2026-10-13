<?php

declare(strict_types=1);

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A background task's status; the same fields as the `background-task.finished` event.
 *
 * @property array{id: string, type: string, subject_id: int|null, status: string, message: string|null, admin_id: int, updated_at: string} $resource
 */
class BackgroundTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource['id'],
            'type' => (string) $this->resource['type'],
            /** @var int|null */
            'subject_id' => $this->resource['subject_id'],
            /** queued | running | completed | failed */
            'status' => (string) $this->resource['status'],
            /** @var string|null */
            'message' => $this->resource['message'],
            'updated_at' => (string) $this->resource['updated_at'],
        ];
    }
}
