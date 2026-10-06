<?php

declare(strict_types=1);

namespace App\Http\Resources\Docs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'rank_order' => $this->rank_order,
            'layout_structure' => $this->layout_structure,
        ];
    }
}
