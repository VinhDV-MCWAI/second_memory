<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\ContentConst;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Repositories\Concerns\MovesAmongSiblings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Queries of the Obsidian import (ADR-0022). Category names are matched ignoring case, like their unique index.
 */
final class ArticleImportRepository
{
    use MovesAmongSiblings;

    /** Typed as the trait expects: the same model as ArticleRepository */
    protected Model $model;

    public function __construct(Article $model)
    {
        $this->model = $model;
    }

    /**
     * Every article the importer owns, hidden ones included, keyed by vault path.
     *
     * @return Collection<string, Article>
     */
    public function importedRows(): Collection
    {
        return Article::query()
            ->with('categories:id')
            ->whereNotNull('external_key')
            ->get()
            ->keyBy('external_key');
    }

    /**
     * @param  list<string>  $names  lower-case
     * @return array<string, int> lower-case name => id
     */
    public function categoryIdsByName(array $names): array
    {
        if ($names === []) {
            return [];
        }

        return ContentCategory::query()
            ->whereIn(DB::raw('lower(name)'), $names)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (ContentCategory $category): array => [mb_strtolower($category->name) => $category->id])
            ->all();
    }

    /**
     * Which of these are slugs of articles that are not imported: a note may link to them by slug.
     *
     * @param  list<string>  $slugs
     * @return list<string>
     */
    public function manualSlugs(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return Article::query()->whereIn('slug', $slugs)->whereNull('external_key')->pluck('slug')->all();
    }

    /**
     * A slug from the title that no article has and that is not in $reserved, as on create in the admin.
     *
     * @param  array<string, true>  $reserved  slugs given to other new notes of this import
     */
    public function freeSlug(string $title, array $reserved): string
    {
        $base = Str::slug($title) ?: ContentConst::ARTICLE_SLUG_FALLBACK;
        $slug = $base;
        for ($i = 2; isset($reserved[$slug]) || Article::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $categoryIds
     */
    public function create(array $attributes, array $categoryIds): Article
    {
        $parentId = self::idOrNull($attributes['parent_id'] ?? null);
        $article = Article::query()->create([...$attributes, 'sort_order' => $this->nextPosition($parentId)]);
        $article->categories()->sync($categoryIds);

        return $article;
    }

    /**
     * A new parent puts the article last among its new siblings, as in the admin.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $categoryIds
     */
    public function update(Article $article, array $attributes, array $categoryIds): void
    {
        $parentId = self::idOrNull($attributes['parent_id'] ?? null);
        if ($parentId !== $article->parent_id) {
            $attributes['sort_order'] = $this->nextPosition($parentId);
        }
        $article->update($attributes);
        $article->categories()->sync($categoryIds);
    }

    public function hide(Article $article): void
    {
        $article->update(['is_public' => false]);
    }
}
