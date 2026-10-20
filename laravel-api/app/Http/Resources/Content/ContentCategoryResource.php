<?php

declare(strict_types=1);

namespace App\Http\Resources\Content;

use App\Models\Content\CategoryAttachment;
use App\Models\Content\ContentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A group (`parent_id` null) or a category inside one, as the admin screens edit it (ADR-0022).
 *
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
            'parent_id' => $this->parent_id,
            /** @var string|null */
            'parent_name' => $this->getAttribute('parent_name'),
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            /** Markdown */
            'description' => $this->description,
            /** Place among its siblings, 1 first; changed with `move` */
            'sort_order' => (int) $this->sort_order,
            'is_public' => (bool) $this->is_public,
            'article_count' => (int) $this->articles_count,
            /** Categories inside this group */
            'children_count' => (int) $this->children_count,
            /** Links or files shown as the category's evidence, in display order */
            'attachments' => $this->attachments->map(fn (CategoryAttachment $attachment): array => [
                'id' => $attachment->id,
                'title' => $attachment->title,
                'url' => $attachment->url,
                'media_id' => $attachment->media_id,
                'note' => $attachment->note,
                'file' => $attachment->media === null ? null : new AttachedFileResource($attachment->media),
            ])->all(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
