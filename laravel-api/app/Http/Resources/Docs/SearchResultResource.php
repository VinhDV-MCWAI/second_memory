<?php

declare(strict_types=1);

namespace App\Http\Resources\Docs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'categories' => CategoryResource::collection($this->resource['categories']),
            'entries' => EntryResource::collection($this->resource['entries']),
            'descriptions' => SearchDescriptionResource::collection($this->resource['descriptions']),
        ];
    }
}
