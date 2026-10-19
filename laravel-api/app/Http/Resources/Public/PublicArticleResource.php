<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A public article (ADR-0021). The Markdown body becomes HTML here; raw HTML in it is escaped and
 * unsafe links (javascript:, data:…) are dropped, so the page can print `body_html` as is.
 *
 * @mixin Article
 */
class PublicArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'key' => (string) $this->public_key,
            'summary' => $this->summary,
            'body_html' => Str::markdown((string) $this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            'updated_at' => (string) $this->updated_at?->toIso8601String(),
            /** @var array{name: string, slug: string} */
            'category' => ['name' => (string) $this->category->name, 'slug' => (string) $this->category->slug],
        ];
    }
}
