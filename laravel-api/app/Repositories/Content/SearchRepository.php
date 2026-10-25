<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\SearchConst;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Search over articles, categories and the names of files and folders (ADR-0009, ADR-0022). Every
 * text is compared through f_unaccent(lower(...)), so "hang doi" finds "Hàng đợi". Articles and
 * categories keep their weighted vector in `search_tsv` (title / name first, then summary, then
 * body / description) and their title / name in `search_text` for the typo fallback. Files come
 * from the file manager's repository, which knows the locked folders; `$blocked` null means the
 * admin may not see Files. `$visibleOnly` limits articles and categories to what the public site shows.
 */
final class SearchRepository
{
    public function __construct(private readonly MediaRepository $media) {}

    /**
     * Runs the searches of `$fuzzy` true calls with the typo threshold set, local to one transaction
     * (the `<%` operator reads it; a session-level setting would outlive the request on a persistent connection).
     *
     * @template T
     *
     * @param  callable(): T  $run
     * @return T
     */
    public function withFuzzyThreshold(callable $run): mixed
    {
        return DB::transaction(function () use ($run): mixed {
            DB::select("SELECT set_config('pg_trgm.word_similarity_threshold', ?, true)", [SearchConst::FUZZY_THRESHOLD]);

            return $run();
        });
    }

    /**
     * @param  list<string>  $words
     * @return Collection<int, Article>
     */
    public function articles(array $words, bool $fuzzy, bool $visibleOnly, int $limit): Collection
    {
        $query = Article::query()
            ->select(['article.id', 'article.title', 'article.slug', 'article.summary', 'article.body', 'article.is_public'])
            ->when($visibleOnly, fn (Builder $query) => $query->visible());

        return $this->match($query, 'article', $words, $fuzzy)->limit($limit)->get();
    }

    /**
     * @param  list<string>  $words
     * @return Collection<int, ContentCategory>
     */
    public function categories(array $words, bool $fuzzy, bool $visibleOnly, int $limit): Collection
    {
        $query = ContentCategory::query()
            ->with('parent:id,name,slug')
            ->select(['content_category.id', 'content_category.parent_id', 'content_category.name', 'content_category.slug', 'content_category.description', 'content_category.is_public'])
            ->when($visibleOnly, fn (Builder $query) => $query->visible());

        return $this->match($query, 'content_category', $words, $fuzzy)->limit($limit)->get();
    }

    /**
     * Names that contain every word; `$fuzzy` tolerates typos (call inside withFuzzyThreshold()).
     *
     * @param  list<string>  $words
     * @param  list<string>|null  $blocked
     * @return list<MediaMgmt>
     */
    public function files(array $words, bool $fuzzy, ?array $blocked): array
    {
        return $blocked === null ? [] : $this->media->searchNames($words, $fuzzy, $blocked, SearchConst::LIMIT)->all();
    }

    /**
     * @template TModel of Article|ContentCategory
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $words
     * @return Builder<TModel>
     */
    private function match(Builder $query, string $table, array $words, bool $fuzzy): Builder
    {
        if ($fuzzy) {
            // With the GiST index, ORDER BY `<<->` walks the index in distance order and stops after the limit
            $text = implode(' ', $words);

            return $query->whereRaw("f_unaccent(lower(?)) <% {$table}.search_text", [$text])
                ->orderByRaw("f_unaccent(lower(?)) <<-> {$table}.search_text", [$text]);
        }

        // Letters and digits only, so no operator leaks in; the last word is a prefix ("hang do" finds "hàng đợi")
        $tsQuery = implode(' & ', $words).':*';

        return $query->whereRaw("{$table}.search_tsv @@ to_tsquery('simple', f_unaccent(lower(?)))", [$tsQuery])
            ->orderByRaw("ts_rank(?::float4[], {$table}.search_tsv, to_tsquery('simple', f_unaccent(lower(?)))) DESC", [SearchConst::RANK_WEIGHTS, $tsQuery])
            ->orderBy("{$table}.id");
    }
}
