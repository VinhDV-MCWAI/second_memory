<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Models\Master\AdminSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A device the signed-in admin is signed in on (AUTH-04). The session id itself is never sent.
 *
 * @mixin AdminSession
 */
class AdminSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            /** Raw User-Agent header; the screen turns it into a browser and system name */
            'user_agent' => $this->user_agent,
            'signed_in_at' => $this->created_at->toIso8601String(),
            /** Refreshed at most every 5 minutes */
            'last_used_at' => $this->last_used_at->toIso8601String(),
            /**
             * Until when this device stays signed in (remember me); null when it is not remembered
             *
             * @var string|null
             */
            'remembered_until' => $this->remembered_until?->toIso8601String(),
            /**
             * The session making this request
             *
             * @var bool
             */
            'is_current' => $request->hasSession() && $this->session_id === $request->session()->getId(),
        ];
    }
}
