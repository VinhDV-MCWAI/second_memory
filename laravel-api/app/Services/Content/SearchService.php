<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\MediaConst;
use App\Constants\SearchConst;
use App\Enums\SearchMatch;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use App\Repositories\Content\SearchRepository;
use App\Services\Media\FolderAccess;
use App\Services\Media\MediaInfoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * One search box over articles, categories and the names of files and folders (ADR-0009,
 * ADR-0022): exact words first, the typo-tolerant trigram match only when that finds nothing at
 * all. The admin search sees private articles and categories; files only for an admin who may see
 * Files, never in locked folders they may not use, never in the trash. The public search sees what
 * the public site shows and never files.
 *
 * @phpstan-type ArticleHit array{id: int, title: string, slug: string, snippet: string|null, is_public: bool}
 * @phpstan-type CategoryHit array{id: int, name: string, slug: string, group: string|null, is_public: bool}
 * @phpstan-type FileHit array{id: int, is_file: bool, name: string, path: string, size: int|null, thumbnail_url: string|null}
 * @phpstan-type PublicArticleHit array{title: string, slug: string, summary: string|null}
 * @phpstan-type PublicCategoryHit array{name: string, slug: string, group: string|null}
 */
final class SearchService
{
    public function __construct(
        private readonly SearchRepository $search,
        private readonly FolderAccess $access,
    ) {}

    /**
     * @return array{match: SearchMatch, articles: list<ArticleHit>, categories: list<CategoryHit>, files: list<FileHit>}
     */
    public function search(string $query): array
    {
        $admin = Auth::user();
        $blocked = $admin instanceof AdminMst && $admin->can(MediaConst::LIST_PERMISSION) ? $this->access->blockedFolders() : null;
        [$match, $articles, $categories, $files] = $this->run(
            $query,
            fn (array $words, bool $fuzzy): array => [
                $this->search->articles($words, $fuzzy, false, SearchConst::LIMIT)->all(),
                $this->search->categories($words, $fuzzy, false, SearchConst::LIMIT)->all(),
                $this->search->files($words, $fuzzy, $blocked),
            ],
        );

        return [
            'match' => $match,
            'articles' => array_map(fn (Article $article): array => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'snippet' => $this->snippet($article->summary),
                'is_public' => $article->is_public,
            ], $articles),
            'categories' => array_map(fn (ContentCategory $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'group' => $category->parent?->name,
                'is_public' => (bool) $category->is_public,
            ], $categories),
            'files' => array_map(fn (MediaMgmt $file): array => [
                'id' => (int) $file->id,
                'is_file' => (bool) $file->is_file,
                'name' => (string) $file->original_name,
                'path' => (string) $file->virtual_path,
                'size' => $file->size,
                'thumbnail_url' => MediaInfoService::thumbnailUrl($file),
            ], $files),
        ];
    }

    /**
     * @return array{match: SearchMatch, articles: list<PublicArticleHit>, categories: list<PublicCategoryHit>}
     */
    public function publicSearch(string $query): array
    {
        [$match, $articles, $categories] = $this->run(
            $query,
            fn (array $words, bool $fuzzy): array => [
                $this->search->articles($words, $fuzzy, true, SearchConst::PUBLIC_ARTICLE_LIMIT)->all(),
                $this->search->categories($words, $fuzzy, true, SearchConst::LIMIT)->all(),
            ],
        );

        return [
            'match' => $match,
            'articles' => array_map(fn (Article $article): array => [
                'title' => $article->title,
                'slug' => $article->slug,
                'summary' => $article->summary,
            ], $articles),
            'categories' => array_map(fn (ContentCategory $category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'group' => $category->parent?->name,
            ], $categories),
        ];
    }

    /**
     * Exact search, then the fuzzy one when every list came back empty: the match, then the lists
     * `$lists` returns for the words. The request holds at least one letter or digit (SearchConst::WORD_PATTERN).
     *
     * @param  callable(list<string>, bool): list<list<mixed>>  $lists
     * @return list<mixed>
     */
    private function run(string $query, callable $lists): array
    {
        // Letters and digits only: the words become a to_tsquery expression
        $words = preg_split(SearchConst::WORD_SEPARATOR, $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $exact = $lists($words, false);
        if (array_filter($exact) !== []) {
            return [SearchMatch::EXACT, ...$exact];
        }

        return [SearchMatch::FUZZY, ...$this->search->withFuzzyThreshold(fn (): array => $lists($words, true))];
    }

    private function snippet(?string $text): ?string
    {
        return $text === null ? null : Str::limit($text, SearchConst::SNIPPET_LENGTH);
    }
}
