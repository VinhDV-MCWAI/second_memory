<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Models\User\EndUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in end user (ADR-0020). No id: the public site never needs one.
 *
 * @mixin EndUser
 */
class CurrentEndUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email' => (string) $this->email,
            'display_name' => (string) $this->display_name,
        ];
    }
}
