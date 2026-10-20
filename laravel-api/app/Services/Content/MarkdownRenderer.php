<?php

declare(strict_types=1);

namespace App\Services\Content;

use Illuminate\Support\Str;

/**
 * Markdown of articles and category descriptions → HTML the pages print as is (ADR-0021): GitHub
 * flavour, raw HTML escaped, unsafe links (javascript:, data:…) dropped.
 */
final class MarkdownRenderer
{
    public function toHtml(string $markdown): string
    {
        return Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
