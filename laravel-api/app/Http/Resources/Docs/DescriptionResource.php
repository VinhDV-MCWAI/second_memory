<?php

declare(strict_types=1);

namespace App\Http\Resources\Docs;

use App\Models\Management\EntryDescriptionMgmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EntryDescriptionMgmt
 */
class DescriptionResource extends JsonResource
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
            'title' => $this->title,
            'summary' => $this->summary,
            'article' => $this->article,
            'rank_order' => $this->rank_order,
        ];
    }
}
