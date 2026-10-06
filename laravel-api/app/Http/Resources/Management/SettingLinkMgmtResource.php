<?php

declare(strict_types=1);

namespace App\Http\Resources\Management;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingLinkMgmtResource extends JsonResource
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
            'key' => (string) $this->key,
            'value' => (string) $this->value,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
