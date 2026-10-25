<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Models\User\EndUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An end user as the admin screens list it (ADR-0020). Never the password.
 *
 * @mixin EndUser
 */
class EndUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'username' => (string) $this->username,
            'display_name' => (string) $this->display_name,
            'is_active' => (bool) $this->is_active,
            'saved_count' => (int) $this->saved_items_count,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
