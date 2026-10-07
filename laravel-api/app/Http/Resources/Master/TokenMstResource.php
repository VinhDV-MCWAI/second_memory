<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\Master\TokenMst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TokenMst
 */
class TokenMstResource extends JsonResource
{
    use FormatsDates;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'account_id' => (int) $this->account_id,
            'device_name' => (string) $this->device_name,
            'ip_address' => (string) $this->ip_address,
            'expired_at' => (string) $this->expired_at,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
