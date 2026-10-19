<?php

declare(strict_types=1);

namespace App\Http\Resources\Content;

use App\Models\Content\ContentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContentCategory
 */
class ContentCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'description' => $this->description,
            'sort_order' => (int) $this->sort_order,
            'is_public' => (bool) $this->is_public,
            'article_count' => (int) $this->articles_count,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
