<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\SearchConst;
use Illuminate\Support\Str;

/**
 * Finds the searched words in a text the way the search compares them (accent-free, lower case,
 * words matched from their start; ADR-0024) and cuts the passage around the first one, with the
 * words marked. Works per character, so the passage keeps the accents of the original.
 */
final class SearchHighlighter
{
    /** Characters kept before the first match */
    private const LEAD = 60;

    private const ELLIPSIS = '…';

    /**
     * Every word starts a word of the text.
     *
     * @param  list<string>  $words
     */
    public static function containsAll(string $text, array $words): bool
    {
        $folded = self::fold($text)[0];
        foreach ($words as $word) {
            if (self::starts($folded, self::fold($word)[0]) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Safe HTML: the passage around the first word found, escaped, the words in `<mark>`; null when
     * none is in the text.
     *
     * @param  list<string>  $words
     */
    public static function snippet(string $text, array $words): ?string
    {
        [$folded, $origin, $chars] = self::fold($text);
        /** @var array<int, int> $marks first character → character after the match */
        $marks = [];
        foreach ($words as $word) {
            $needle = self::fold($word)[0];
            foreach (self::starts($folded, $needle) as $at) {
                $start = $origin[$at];
                $marks[$start] = max($marks[$start] ?? 0, $origin[$at + strlen($needle) - 1] + 1);
            }
        }
        if ($marks === []) {
            return null;
        }
        ksort($marks);

        $from = max(0, (int) array_key_first($marks) - self::LEAD);
        // Start and end on a space, so no word is cut
        while ($from > 0 && $chars[$from - 1] !== ' ') {
            $from--;
        }
        $to = min(count($chars), $from + SearchConst::SNIPPET_LENGTH);
        while ($to < count($chars) && $chars[$to] !== ' ') {
            $to++;
        }

        $html = $from > 0 ? self::ELLIPSIS : '';
        for ($i = $from; $i < $to;) {
            if (isset($marks[$i])) {
                $end = min($marks[$i], $to);
                $html .= '<mark>'.e(implode('', array_slice($chars, $i, $end - $i))).'</mark>';
                $i = $end;

                continue;
            }
            $html .= e($chars[$i]);
            $i++;
        }

        return $html.($to < count($chars) ? self::ELLIPSIS : '');
    }

    /**
     * Readable text of Markdown: no code blocks, markers or link targets; one line. HTML stays as
     * text, the way the article page shows it (the renderer escapes it).
     */
    public static function plainText(string $markdown): string
    {
        $text = (string) preg_replace('/^(```|~~~).*?^\1\s*$/ms', ' ', $markdown);
        $text = (string) preg_replace('/!?\[\[([^\]|#]+)(?:#[^\]|]*)?(?:\|([^\]]*))?\]\]/', '$2 $1', $text);
        $text = (string) preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        $text = (string) preg_replace('/^\s{0,3}(#{1,6}|>|[-*+]|\d+\.)\s+/m', '', $text);
        $text = str_replace(['**', '__', '`', '~~'], '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * The text accent-free and lower case, the original character each folded byte came from, and
     * the original characters.
     *
     * @return array{0: string, 1: list<int>, 2: list<string>}
     */
    private static function fold(string $text): array
    {
        $chars = mb_str_split($text);
        $folded = '';
        $origin = [];
        foreach ($chars as $index => $char) {
            $ascii = strtolower(Str::ascii($char));
            // A character with no ASCII form stays a non-letter, so positions keep matching
            $piece = $ascii === '' ? ' ' : $ascii;
            $folded .= $piece;
            array_push($origin, ...array_fill(0, strlen($piece), $index));
        }

        return [$folded, $origin, $chars];
    }

    /**
     * Byte offsets in $folded where $needle begins a word.
     *
     * @return list<int>
     */
    private static function starts(string $folded, string $needle): array
    {
        if ($needle === '') {
            return [];
        }
        preg_match_all('/(?<![a-z0-9])'.preg_quote($needle, '/').'/', $folded, $found, PREG_OFFSET_CAPTURE);

        return array_map(fn (array $match): int => (int) $match[1], $found[0]);
    }
}
