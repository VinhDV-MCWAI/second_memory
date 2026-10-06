<?php

declare(strict_types=1);

namespace App\Http\Resources\Docs;

use App\Models\Management\EntryMgmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EntryMgmt
 */
class EntryDetailResource extends JsonResource
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
            'layout_structure' => $this->layout_structure,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'descriptions' => DescriptionResource::collection($this->whenLoaded('descriptions')),
        ];
    }
}
