<?php

declare(strict_types=1);

namespace App\Services\Content;

/**
 * The Obsidian-style syntax of articles (ADR-0022), found outside fenced code blocks:
 * `[[slug]]`, `[[slug#Heading|text]]` link to an article; a line holding only `![[slug]]` or
 * `![[slug#Heading]]` embeds it; a line holding only `![[file:{id}]]` or `![[file:{id}|caption]]`
 * embeds a file of the file manager.
 */
final class WikiSyntax
{
    /** `target`, then an optional `#heading`, then an optional `|text` */
    private const LINK = '/!?\[\[([^\]\|#]+)(?:#([^\]\|]+))?(?:\|([^\]]+))?\]\]/u';

    private const BLOCK = '/^\s*!\[\[([^\]\|#]+)(?:#([^\]\|]+))?(?:\|([^\]]+))?\]\]\s*$/u';

    private const FENCE_OPEN = '/^ {0,3}(`{3,}|~{3,})/';

    private const HEADING = '/^ {0,3}(#{1,6})\s+(.*?)\s*#*\s*$/u';

    public const FILE_PREFIX = 'file:';

    /**
     * Rewrites the Markdown line by line: fenced code stays as it is, a block embed becomes
     * `$block(target, heading, text)` on its own paragraph, a link `$link(target, heading, text)`.
     * Inline code spans are left alone too.
     *
     * @param  callable(string, ?string, ?string): string  $block
     * @param  callable(string, ?string, ?string): string  $link
     */
    public static function rewrite(string $markdown, callable $block, callable $link): string
    {
        $out = [];
        self::walk($markdown, function (string $line, bool $code) use (&$out, $block, $link): void {
            if ($code) {
                $out[] = $line;
            } elseif (preg_match(self::BLOCK, $line, $m) === 1) {
                array_push($out, '', $block(trim($m[1]), self::part($m, 2), self::part($m, 3)), '');
            } else {
                $out[] = self::rewriteInline($line, $link);
            }
        });

        return implode("\n", $out);
    }

    /**
     * Ids of the files embedded as blocks, in order of appearance, each once.
     *
     * @return list<int>
     */
    public static function fileIds(string $markdown): array
    {
        $ids = [];
        self::walk($markdown, function (string $line, bool $code) use (&$ids): void {
            if (! $code && preg_match(self::BLOCK, $line, $m) === 1 && str_starts_with(trim($m[1]), self::FILE_PREFIX)) {
                $id = substr(trim($m[1]), strlen(self::FILE_PREFIX));
                if (ctype_digit($id)) {
                    $ids[] = (int) $id;
                }
            }
        });

        return array_values(array_unique($ids));
    }

    /**
     * The part of the Markdown under the heading whose text is `$heading` (case ignored), down to
     * the next heading of the same or a higher level; null when there is no such heading.
     */
    public static function section(string $markdown, string $heading): ?string
    {
        $wanted = mb_strtolower(trim($heading));
        $level = null;
        $lines = [];
        self::walk($markdown, function (string $line, bool $code) use (&$level, &$lines, $wanted): void {
            $isHeading = ! $code && preg_match(self::HEADING, $line, $m) === 1;
            if ($level === null) {
                if ($isHeading && mb_strtolower(trim($m[2])) === $wanted) {
                    $level = strlen($m[1]);
                    $lines[] = $line;
                }

                return;
            }
            if ($level > 0 && $isHeading && strlen($m[1]) <= $level) {
                $level = 0;
            }
            if ($level > 0) {
                $lines[] = $line;
            }
        });

        return $lines === [] ? null : implode("\n", $lines);
    }

    /**
     * Calls `$visit(line, insideFencedCode)` for every line; fence lines count as code.
     *
     * @param  callable(string, bool): void  $visit
     */
    private static function walk(string $markdown, callable $visit): void
    {
        $fence = null;
        foreach (preg_split('/\R/u', $markdown) ?: [] as $line) {
            if ($fence !== null) {
                $visit($line, true);
                if (preg_match('/^ {0,3}'.preg_quote($fence[0], '/').'{'.strlen($fence).',}\s*$/', $line) === 1) {
                    $fence = null;
                }

                continue;
            }
            if (preg_match(self::FENCE_OPEN, $line, $m) === 1) {
                $fence = $m[1];
                $visit($line, true);

                continue;
            }
            $visit($line, false);
        }
    }

    /**
     * @param  callable(string, ?string, ?string): string  $link
     */
    private static function rewriteInline(string $line, callable $link): string
    {
        // Odd pieces are inline code spans: `[[x]]` inside them stays literal
        $pieces = preg_split('/(`+[^`]*`+)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$line];
        foreach ($pieces as $index => $piece) {
            if ($index % 2 === 0) {
                $pieces[$index] = (string) preg_replace_callback(
                    self::LINK,
                    fn (array $m): string => $link(trim($m[1]), self::part($m, 2), self::part($m, 3)),
                    $piece,
                );
            }
        }

        return implode('', $pieces);
    }

    /**
     * @param  array<int, string>  $match
     */
    private static function part(array $match, int $index): ?string
    {
        $value = trim($match[$index] ?? '');

        return $value === '' ? null : $value;
    }
}
