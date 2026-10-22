<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Enums\AuditEvent;
use App\Models\Content\Article;
use App\Repositories\Content\ArticleImportRepository;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Syncs the complete list of published Obsidian notes into articles (ADR-0022): upsert by
 * `external_key` (the vault path), hide imported articles missing from the list, never write an
 * unchanged one. The importer owns title, summary, body, parent and categories of its articles;
 * `is_public` stays the owner's choice except that a new note starts public and a removed one is
 * hidden. The slug is set once on create, like in the admin.
 *
 * Notes name each other the way Obsidian does: `parent` and the targets of `[[…]]` / `![[…]]` are a
 * vault path or a file name, with or without `.md`, ignoring case. A link to a published note
 * becomes `[[its slug]]`; a link to an article that is not imported keeps working by its slug; any
 * other link (a note that is not published, a picture of the vault, `file:`) becomes its text.
 *
 * @phpstan-type Note array{external_key: string, title: string, summary?: string|null, body: string|null, parent?: string|null, categories?: list<string>}
 * @phpstan-type Report array{created: int, updated: int, unchanged: int, hidden: int, unknown_categories: list<string>, unknown_links: list<string>}
 * @phpstan-type Index array{paths: array<string, string>, names: array<string, list<string>>}
 */
final class ArticleImportService
{
    private const NOTE_SUFFIX = '.md';

    public function __construct(
        private readonly ArticleImportRepository $imports,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{notes: list<Note>, dry_run?: bool}  $payload
     * @return Report
     *
     * @throws ValidationException when a parent is not a published note, parents form a loop or a tree gets too deep
     */
    public function import(array $payload): array
    {
        $notes = [];
        foreach ($payload['notes'] as $note) {
            $notes[$note['external_key']] = $note;
        }
        $dryRun = (bool) ($payload['dry_run'] ?? false);

        $index = $this->index(array_keys($notes));
        $parents = $this->parents($notes, $index);
        $rows = $this->imports->importedRows();
        $keyById = $rows->mapWithKeys(fn (Article $row, string $key): array => [$row->id => $key])->all();

        // Parents first, so a new parent has its id before its sub-pages are written
        $order = $this->parentsFirst($parents);
        $slugs = [];
        $reserved = [];
        foreach ($order as $key) {
            $slugs[$key] = $rows->has($key) ? $rows[$key]->slug : $this->imports->freeSlug($notes[$key]['title'], $reserved);
            $reserved[$slugs[$key]] = true;
        }

        [$categoryIds, $unknownCategories] = $this->categories($notes);
        $bodies = $this->bodies($notes, $index, $slugs, $unknownLinks);

        $report = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'hidden' => 0, 'unknown_categories' => $unknownCategories, 'unknown_links' => $unknownLinks];
        $writes = [];
        foreach ($order as $key) {
            $wanted = [
                'title' => $notes[$key]['title'],
                'summary' => $notes[$key]['summary'] ?? null,
                'body' => $bodies[$key],
                'parent' => $parents[$key],
                'category_ids' => $categoryIds[$key],
            ];
            $row = $rows->get($key);
            if ($row === null) {
                $report['created']++;
                $writes[] = [$key, $wanted, null];
            } elseif ($this->current($row, $keyById) !== $wanted) {
                $report['updated']++;
                $writes[] = [$key, $wanted, $row];
            } else {
                $report['unchanged']++;
            }
        }

        $removed = $rows->filter(fn (Article $row, string $key): bool => ! isset($notes[$key]) && $row->is_public);
        $report['hidden'] = $removed->count();

        if ($dryRun) {
            return $report;
        }

        $idByKey = array_flip($keyById);
        foreach ($writes as [$key, $wanted, $row]) {
            $attributes = [
                'title' => $wanted['title'],
                'summary' => $wanted['summary'],
                'body' => $wanted['body'],
                'parent_id' => $wanted['parent'] === null ? null : $idByKey[$wanted['parent']],
            ];
            if ($row === null) {
                $idByKey[$key] = $this->create($key, $slugs[$key], $attributes, $wanted['category_ids']);
            } else {
                $this->update($row, $attributes, $wanted['category_ids']);
            }
        }
        foreach ($removed as $row) {
            $this->imports->hide($row);
            $this->audit($row->id, AuditEvent::UPDATED, ['is_public' => true], ['is_public' => false]);
        }

        return $report;
    }

    /**
     * Lookups for Obsidian names: path without `.md` → key, and file name → keys (shortest first).
     *
     * @param  list<string>  $keys
     * @return Index
     */
    private function index(array $keys): array
    {
        $index = ['paths' => [], 'names' => []];
        foreach ($keys as $key) {
            $path = $this->withoutSuffix(mb_strtolower($key));
            $index['paths'][$path] = $key;
            $index['names'][$this->fileName($path)][] = $key;
        }
        foreach ($index['names'] as &$candidates) {
            usort($candidates, fn (string $a, string $b): int => [mb_strlen($a), $a] <=> [mb_strlen($b), $b]);
        }

        return $index;
    }

    /**
     * The published note a link or `parent` names, as Obsidian finds it: the exact path, else the
     * notes with that file name (and ending in that path), the one in the linking note's folder
     * first, then the shortest path.
     *
     * @param  Index  $index
     */
    private function resolve(string $target, string $from, array $index): ?string
    {
        $path = $this->withoutSuffix(ltrim(mb_strtolower(trim($target)), '/'));
        if (isset($index['paths'][$path])) {
            return $index['paths'][$path];
        }
        $candidates = array_values(array_filter(
            $index['names'][$this->fileName($path)] ?? [],
            fn (string $key): bool => str_ends_with($this->withoutSuffix(mb_strtolower($key)), '/'.$path),
        ));
        $folder = $this->folder($from);
        foreach ($candidates as $key) {
            if ($this->folder($key) === $folder) {
                return $key;
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * @param  array<string, Note>  $notes
     * @param  Index  $index
     * @return array<string, string|null> key => key of the parent note
     *
     * @throws ValidationException
     */
    private function parents(array $notes, array $index): array
    {
        $parents = [];
        $position = 0;
        foreach ($notes as $key => $note) {
            $target = trim((string) ($note['parent'] ?? ''));
            // Obsidian writes a link property as "[[Note]]"
            $target = (string) preg_replace('/^\[\[([^\]|#]*)(?:[#|][^\]]*)?\]\]$/u', '$1', $target);
            $parents[$key] = $target === '' ? null : $this->resolve($target, $key, $index);
            if ($target !== '' && $parents[$key] === null) {
                throw ValidationException::withMessages(["notes.{$position}.parent" => Messages::E0052]);
            }
            $position++;
        }

        return $parents;
    }

    /**
     * Keys ordered by depth, the list order kept within one depth.
     *
     * @param  array<string, string|null>  $parents
     * @return list<string>
     *
     * @throws ValidationException when parents form a loop or a tree is deeper than ARTICLE_DEPTH_MAX
     */
    private function parentsFirst(array $parents): array
    {
        $positions = array_flip(array_keys($parents));
        $depths = [];
        foreach ($parents as $key => $parent) {
            $seen = [$key => true];
            $depth = 1;
            for ($up = $parent; $up !== null; $up = $parents[$up]) {
                if (isset($seen[$up])) {
                    throw ValidationException::withMessages(["notes.{$positions[$key]}.parent" => Messages::E0053]);
                }
                $seen[$up] = true;
                $depth++;
            }
            if ($depth > ContentConst::ARTICLE_DEPTH_MAX) {
                throw ValidationException::withMessages(["notes.{$positions[$key]}.parent" => Messages::E0050]);
            }
            $depths[$key] = $depth;
        }

        $keys = array_keys($parents);
        usort($keys, fn (string $a, string $b): int => [$depths[$a], $positions[$a]] <=> [$depths[$b], $positions[$b]]);

        return $keys;
    }

    /**
     * Category ids of every note (sorted) and the names no category has (first spelling).
     *
     * @param  array<string, Note>  $notes
     * @return array{array<string, list<int>>, list<string>}
     */
    private function categories(array $notes): array
    {
        $spellings = [];
        foreach ($notes as $note) {
            foreach ($note['categories'] ?? [] as $name) {
                $spellings[mb_strtolower(trim($name))] ??= trim($name);
            }
        }
        $ids = $this->imports->categoryIdsByName(array_map(strval(...), array_keys($spellings)));

        $byNote = [];
        foreach ($notes as $key => $note) {
            $noteIds = [];
            foreach ($note['categories'] ?? [] as $name) {
                $id = $ids[mb_strtolower(trim($name))] ?? null;
                if ($id !== null) {
                    $noteIds[$id] = $id;
                }
            }
            sort($noteIds);
            $byNote[$key] = $noteIds;
        }

        return [$byNote, array_values(array_diff_key($spellings, $ids))];
    }

    /**
     * Bodies with their links rewritten (see the class comment).
     *
     * @param  array<string, Note>  $notes
     * @param  Index  $index
     * @param  array<string, string>  $slugs
     *
     * @param-out list<string>  $unknown  targets that became text, first spelling
     *
     * @return array<string, string>
     */
    private function bodies(array $notes, array $index, array $slugs, ?array &$unknown): array
    {
        // First pass: targets that are no published note may still be slugs of articles made in the admin
        $missing = [];
        foreach ($notes as $from => $note) {
            $collect = function (string $target) use (&$missing, $from, $index): string {
                if ($this->resolve($target, $from, $index) === null) {
                    $missing[$target] = true;
                }

                return '';
            };
            WikiSyntax::rewrite((string) $note['body'], $collect, $collect);
        }
        $manual = array_flip($this->imports->manualSlugs(array_map(strval(...), array_keys($missing))));

        $found = [];
        $bodies = [];
        foreach ($notes as $from => $note) {
            $slugOf = function (string $target) use ($from, $index, $slugs, $manual, &$found): ?string {
                $key = $this->resolve($target, $from, $index);
                if ($key !== null) {
                    return $slugs[$key];
                }
                if (isset($manual[$target])) {
                    return $target;
                }
                $found[mb_strtolower($target)] ??= $target;

                return null;
            };
            $bodies[$from] = WikiSyntax::rewrite(
                (string) $note['body'],
                fn (string $target, ?string $heading, ?string $text): string => $this->link('![[', $slugOf($target), $target, $heading, $text),
                fn (string $target, ?string $heading, ?string $text): string => $this->link('[[', $slugOf($target), $target, $heading, $text),
            );
        }
        $unknown = array_values($found);

        return $bodies;
    }

    /** `[[slug#heading|text]]` (or `![[…]]`), or just the text when the target is no article */
    private function link(string $open, ?string $slug, string $target, ?string $heading, ?string $text): string
    {
        if ($slug === null) {
            return $text ?? $target;
        }

        return $open.$slug.($heading === null ? '' : '#'.$heading).($text === null ? '' : '|'.$text).']]';
    }

    /**
     * The fields the importer owns, in the form they are compared with the wanted ones.
     *
     * @param  array<int, string>  $keyById
     * @return array{title: string, summary: string|null, body: string, parent: string|null, category_ids: list<int>}
     */
    private function current(Article $row, array $keyById): array
    {
        $categoryIds = $row->categories->modelKeys();
        sort($categoryIds);

        return [
            'title' => $row->title,
            'summary' => $row->summary,
            'body' => $row->body,
            // A parent set in the admin that is not imported never equals a note's parent
            'parent' => $row->parent_id === null ? null : ($keyById[$row->parent_id] ?? '#'.$row->parent_id),
            'category_ids' => array_map(intval(...), $categoryIds),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $categoryIds
     */
    private function create(string $key, string $slug, array $attributes, array $categoryIds): int
    {
        $values = [...$attributes, 'slug' => $slug, 'external_key' => $key, 'is_public' => true];
        $article = $this->imports->create($values, $categoryIds);
        $this->audit($article->id, AuditEvent::CREATED, null, [...$values, 'category_ids' => $categoryIds]);

        return $article->id;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $categoryIds
     */
    private function update(Article $row, array $attributes, array $categoryIds): void
    {
        $before = [
            'title' => $row->title,
            'summary' => $row->summary,
            'body' => $row->body,
            'parent_id' => $row->parent_id,
            'category_ids' => array_map(intval(...), $row->categories->modelKeys()),
        ];
        $this->imports->update($row, $attributes, $categoryIds);
        $this->audit($row->id, AuditEvent::UPDATED, $before, [...$attributes, 'category_ids' => $categoryIds]);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>  $new
     */
    private function audit(int $id, AuditEvent $event, ?array $old, array $new): void
    {
        // `via` marks the row as written by the importer; the actor is the token's admin
        $this->auditLogger->record(ContentConst::ARTICLE_AUDITABLE_TYPE, $id, $event, $old, [...$new, 'via' => ContentConst::IMPORT_AUDIT_VIA]);
    }

    private function withoutSuffix(string $path): string
    {
        return str_ends_with($path, self::NOTE_SUFFIX) ? substr($path, 0, -strlen(self::NOTE_SUFFIX)) : $path;
    }

    private function fileName(string $path): string
    {
        $slash = strrpos($path, '/');

        return $slash === false ? $path : substr($path, $slash + 1);
    }

    private function folder(string $key): string
    {
        $slash = strrpos($key, '/');

        return $slash === false ? '' : mb_strtolower(substr($key, 0, $slash));
    }
}
