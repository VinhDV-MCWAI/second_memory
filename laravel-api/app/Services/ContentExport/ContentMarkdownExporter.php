<?php

declare(strict_types=1);

namespace App\Services\ContentExport;

use App\Repositories\ContentExport\ContentExportRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Exports the legacy CMS content (categories → entries → descriptions) to
 * Markdown files that can be dropped into an Obsidian vault (REQ-001 US-2).
 *
 * Layout of the output directory:
 *   <category-slug>/_index.md         category + list of its entries
 *   <category-slug>/<entry-slug>.md   one file per entry with its descriptions
 *   _uncategorized/<entry-slug>.md    entries that no category references
 *   _unlinked/<id>-<title-slug>.md    descriptions that no entry references
 *
 * Categories, entries and descriptions are linked only through their JSON
 * `layout_structure` columns (no foreign keys), so the links are resolved here.
 * The output is deterministic: running the export twice writes the same files.
 */
final class ContentMarkdownExporter
{
    public const UNCATEGORIZED_DIR = '_uncategorized';

    public const UNLINKED_DIR = '_unlinked';

    public const INDEX_FILE = '_index.md';

    public function __construct(
        private readonly ContentExportRepository $repository,
        private readonly TiptapMarkdownConverter $converter,
        private readonly Filesystem $files,
    ) {}

    /**
     * @return array{categories: int, entries: int, descriptions: int, unlinked: int, written: int, unchanged: int}
     */
    public function export(string $directory, bool $includeDeleted = false): array
    {
        $categories = $this->repository->categories($includeDeleted)->keyBy('id');
        $entries = $this->repository->entries($includeDeleted)->keyBy('id');
        $descriptions = $this->repository->descriptions($includeDeleted)->keyBy('id');

        $stats = ['categories' => 0, 'entries' => 0, 'descriptions' => 0, 'unlinked' => 0, 'written' => 0, 'unchanged' => 0];

        // entry id => list of category rows that reference it (first one decides the folder)
        $entryCategories = [];
        foreach ($categories as $category) {
            foreach ($this->idsFromLayout($category->layout_structure, 'entry_mgmt_id') as $entryId) {
                $entryCategories[$entryId][] = $category;
            }
        }

        $categoryDirs = [];
        foreach ($categories as $category) {
            $categoryDirs[$category->id] = $this->uniqueName($categoryDirs, $this->slug($category->slug, $category->name, 'category', (int) $category->id), (int) $category->id);
        }

        $linkedDescriptionIds = [];
        $entryFiles = [];
        foreach ($entries as $entry) {
            $owners = $entryCategories[$entry->id] ?? [];
            $dir = $owners === [] ? self::UNCATEGORIZED_DIR : $categoryDirs[$owners[0]->id];
            $taken = $entryFiles[$dir] ?? [];
            $name = $this->uniqueName($taken, $this->slug($entry->slug, $entry->name, 'entry', (int) $entry->id), (int) $entry->id);
            $entryFiles[$dir][$entry->id] = $name;

            $entryDescriptions = [];
            foreach ($this->idsFromLayout($entry->layout_structure, 'entry_desc_id') as $descriptionId) {
                if (isset($descriptions[$descriptionId])) {
                    $entryDescriptions[] = $descriptions[$descriptionId];
                    $linkedDescriptionIds[$descriptionId] = true;
                }
            }

            $this->write($directory.'/'.$dir.'/'.$name.'.md', $this->entryMarkdown($entry, $owners, $entryDescriptions), $stats);
            $stats['entries']++;
            $stats['descriptions'] += count($entryDescriptions);
        }

        foreach ($categories as $category) {
            $dir = $categoryDirs[$category->id];
            $this->write($directory.'/'.$dir.'/'.self::INDEX_FILE, $this->categoryMarkdown($category, $entryFiles[$dir] ?? [], $entries->all()), $stats);
            $stats['categories']++;
        }

        foreach ($descriptions as $description) {
            if (isset($linkedDescriptionIds[$description->id])) {
                continue;
            }
            $name = $description->id.'-'.$this->slug(null, $description->title, 'description', (int) $description->id);
            $this->write($directory.'/'.self::UNLINKED_DIR.'/'.$name.'.md', $this->unlinkedMarkdown($description), $stats);
            $stats['unlinked']++;
        }

        return $stats;
    }

    /**
     * @param  list<object>  $categories
     * @param  list<object>  $descriptions
     */
    private function entryMarkdown(object $entry, array $categories, array $descriptions): string
    {
        $front = $this->frontMatter([
            'title' => $entry->name,
            'slug' => $entry->slug,
            'categories' => array_map(static fn (object $category): string => $category->name, $categories),
            'status' => (int) $entry->status,
            'is_display' => (bool) $entry->is_display,
            'source' => 'entry_mgmt:'.$entry->id,
            'created' => $entry->created_at,
            'updated' => $entry->updated_at,
        ]);

        $body = '# '.$entry->name;
        foreach ($descriptions as $description) {
            $body .= "\n\n".$this->descriptionSection($description);
        }

        return $front.$body."\n";
    }

    private function descriptionSection(object $description): string
    {
        $section = '## '.$description->title;
        if (($description->summary ?? '') !== '') {
            $section .= "\n\n> ".$description->summary;
        }
        $article = $this->converter->convert($this->decodeJson($description->article));
        if ($article !== '') {
            $section .= "\n\n".$article;
        }

        return $section;
    }

    /**
     * @param  array<int, string>  $entryNames  entry id => file name in this folder
     * @param  array<int|string, object>  $entries
     */
    private function categoryMarkdown(object $category, array $entryNames, array $entries): string
    {
        $front = $this->frontMatter([
            'title' => $category->name,
            'slug' => $category->slug,
            'status' => (int) $category->status,
            'is_display' => (bool) $category->is_display,
            'source' => 'category_mgmt:'.$category->id,
            'created' => $category->created_at,
            'updated' => $category->updated_at,
        ]);

        $body = '# '.$category->name;
        if (($category->description ?? '') !== '') {
            $body .= "\n\n".$category->description;
        }
        if ($entryNames !== []) {
            $body .= "\n";
            foreach ($entryNames as $entryId => $name) {
                $body .= "\n- [[".$name.'|'.$entries[$entryId]->name.']]';
            }
        }

        return $front.$body."\n";
    }

    private function unlinkedMarkdown(object $description): string
    {
        $front = $this->frontMatter([
            'title' => $description->title,
            'status' => (int) $description->status,
            'is_display' => (bool) $description->is_display,
            'source' => 'entry_description_mgmt:'.$description->id,
            'created' => $description->created_at,
            'updated' => $description->updated_at,
        ]);

        return $front.$this->descriptionSection($description)."\n";
    }

    /**
     * YAML front matter. Strings are JSON-encoded, which is valid YAML and safe for any characters.
     *
     * @param  array<string, mixed>  $fields
     */
    private function frontMatter(array $fields): string
    {
        $lines = ['---'];
        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }
            $lines[] = $key.': '.json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $lines[] = '---';

        return implode("\n", $lines)."\n\n";
    }

    /**
     * Collect ids stored under $key anywhere in a (possibly nested) layout structure, in order.
     *
     * @return list<int>
     */
    private function idsFromLayout(mixed $layout, string $key): array
    {
        $items = $this->decodeJson($layout);
        if (! is_array($items)) {
            return [];
        }

        $ids = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (isset($item[$key])) {
                $ids[] = (int) $item[$key];
            }
            if (isset($item['children'])) {
                $ids = array_merge($ids, $this->idsFromLayout($item['children'], $key));
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function decodeJson(mixed $value): array|string|null
    {
        if (! is_string($value)) {
            return is_array($value) ? $value : null;
        }
        $decoded = json_decode($value, true);

        // Array casts store plain strings as JSON strings ("\"text\""), so a decoded string is the real value.
        return is_array($decoded) || is_string($decoded) ? $decoded : $value;
    }

    private function slug(?string $slug, ?string $fallback, string $kind, int $id): string
    {
        $value = Str::slug((string) ($slug ?: $fallback));

        return $value !== '' ? $value : $kind.'-'.$id;
    }

    /**
     * @param  array<int, string>  $taken
     */
    private function uniqueName(array $taken, string $name, int $id): string
    {
        return in_array($name, $taken, true) ? $name.'-'.$id : $name;
    }

    /**
     * @param  array{categories: int, entries: int, descriptions: int, unlinked: int, written: int, unchanged: int}  $stats
     */
    private function write(string $path, string $content, array &$stats): void
    {
        if ($this->files->exists($path) && $this->files->get($path) === $content) {
            $stats['unchanged']++;

            return;
        }
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);
        $stats['written']++;
    }
}
