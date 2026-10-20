<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\ContentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public group or category of the home page (ADR-0022). No ids, no private fields.
 *
 * @mixin ContentCategory
 */
class PublicCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            /** Name of the group it is in; null for a group */
            'group' => $this->getAttribute('parent_name'),
            'article_count' => (int) $this->articles_count,
        ];
    }
}
