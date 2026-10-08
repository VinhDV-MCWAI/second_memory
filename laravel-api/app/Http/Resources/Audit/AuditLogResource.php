<?php

declare(strict_types=1);

namespace App\Http\Resources\Audit;

use App\Models\Audit\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'auditable_type' => (string) $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'event' => $this->event->value,
            /** @var array<string, mixed>|null */
            'old_values' => $this->old_values,
            /** @var array<string, mixed>|null */
            'new_values' => $this->new_values,
            'admin_mst_id' => $this->admin_mst_id,
            'actor_user_name' => $this->actor?->user_name,
            'ip_address' => $this->ip_address,
            // Full timestamp (ISO 8601): the time of day matters in an audit trail
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
