<?php

declare(strict_types=1);

namespace App\Http\Resources\Master;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\Master\FeatureMst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FeatureMst
 */
class FeatureMstResource extends JsonResource
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
            'name' => (string) $this->name,
            'group_name' => (string) $this->group_name,
            'description' => (string) $this->description,
            'status' => (int) $this->status?->value,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
