<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ContentExport\ContentMarkdownExporter;
use Illuminate\Console\Command;

/**
 * One-off export of the legacy CMS content before its tables are dropped (REQ-001, runbook
 * docs/runbooks/content-export.md). Safe to run repeatedly: unchanged files are not rewritten.
 */
final class ExportContentMarkdownCommand extends Command
{
    protected $signature = 'content:export-markdown
        {--path= : Output directory (default: storage/app/exports/content-markdown)}
        {--include-deleted : Also export rows flagged is_delete}';

    protected $description = 'Export categories, entries and entry descriptions to Markdown files for Obsidian';

    public function handle(ContentMarkdownExporter $exporter): int
    {
        $path = (string) ($this->option('path') ?: storage_path('app/exports/content-markdown'));

        $stats = $exporter->export($path, (bool) $this->option('include-deleted'));

        $this->table(
            ['Categories', 'Entries', 'Linked descriptions', 'Unlinked descriptions', 'Files written', 'Files unchanged'],
            [[$stats['categories'], $stats['entries'], $stats['descriptions'], $stats['unlinked'], $stats['written'], $stats['unchanged']]],
        );
        $this->info('Exported to '.$path);

        return self::SUCCESS;
    }
}
