<?php

declare(strict_types=1);

namespace App\Services\ContentExport;

/**
 * Converts a Tiptap (ProseMirror) JSON document into Markdown.
 *
 * Covers the nodes and marks the admin editor produced (StarterKit, Link, Image,
 * Highlight, the custom `video` node). Unknown nodes keep their text so no
 * content is lost.
 */
final class TiptapMarkdownConverter
{
    /**
     * @param  array<string, mixed>|string|null  $document
     */
    public function convert(array|string|null $document): string
    {
        if ($document === null) {
            return '';
        }

        if (is_string($document)) {
            return trim($document);
        }

        $nodes = isset($document['type']) && $document['type'] !== 'doc'
            ? [$document]
            : $this->children($document);

        return trim($this->blocks($nodes));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function blocks(array $nodes): string
    {
        $parts = [];
        foreach ($nodes as $node) {
            $rendered = $this->block($node);
            if ($rendered !== '') {
                $parts[] = $rendered;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function block(array $node): string
    {
        $attrs = $this->attrs($node);

        return match ($node['type'] ?? '') {
            'paragraph' => $this->inline($this->children($node)),
            'heading' => str_repeat('#', max(1, min(6, (int) ($attrs['level'] ?? 1)))).' '.$this->inline($this->children($node)),
            'bulletList' => $this->list($this->children($node), null),
            'orderedList' => $this->list($this->children($node), (int) ($attrs['start'] ?? 1)),
            'blockquote' => $this->prefixLines($this->blocks($this->children($node)), '> '),
            'codeBlock' => '```'.($attrs['language'] ?? '')."\n".$this->plainText($node)."\n```",
            'horizontalRule' => '---',
            'image' => $this->image($attrs),
            'video' => '[video]('.($attrs['src'] ?? '').')',
            'hardBreak' => '',
            default => $this->unknownBlock($node),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function list(array $items, ?int $start): string
    {
        $lines = [];
        $number = $start ?? 1;
        foreach ($items as $item) {
            $marker = $start === null ? '- ' : $number++.'. ';
            $body = $this->blocks($this->children($item));
            $indent = str_repeat(' ', strlen($marker));
            $bodyLines = explode("\n", $body);
            $first = array_shift($bodyLines);
            $rest = array_map(static fn (string $line): string => $line === '' ? '' : $indent.$line, $bodyLines);
            $lines[] = rtrim($marker.$first."\n".implode("\n", $rest));
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function inline(array $nodes): string
    {
        $out = '';
        foreach ($nodes as $node) {
            $type = $node['type'] ?? '';
            if ($type === 'hardBreak') {
                $out .= "  \n";
            } elseif ($type === 'text') {
                $out .= $this->marks((string) ($node['text'] ?? ''), $node['marks'] ?? []);
            } elseif ($type === 'image') {
                $out .= $this->image($this->attrs($node));
            } else {
                $out .= $this->plainText($node);
            }
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $marks
     */
    private function marks(string $text, array $marks): string
    {
        $types = [];
        $href = null;
        foreach ($marks as $mark) {
            if (! is_array($mark)) {
                continue;
            }
            $types[] = $mark['type'] ?? '';
            if (($mark['type'] ?? '') === 'link') {
                $href = $mark['attrs']['href'] ?? null;
            }
        }

        if (in_array('code', $types, true)) {
            $text = '`'.$text.'`';
        }
        foreach (['bold' => '**', 'italic' => '*', 'strike' => '~~', 'highlight' => '=='] as $type => $wrap) {
            if (in_array($type, $types, true)) {
                $text = $wrap.$text.$wrap;
            }
        }
        if ($href !== null) {
            $text = '['.$text.']('.$href.')';
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function image(array $attrs): string
    {
        return '!['.($attrs['alt'] ?? '').']('.($attrs['src'] ?? '').')';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function unknownBlock(array $node): string
    {
        $children = $this->children($node);
        if ($children === []) {
            return $this->plainText($node);
        }

        return $this->blocks($children);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function plainText(array $node): string
    {
        if (($node['type'] ?? '') === 'text') {
            return (string) ($node['text'] ?? '');
        }
        if (($node['type'] ?? '') === 'hardBreak') {
            return "\n";
        }

        return implode('', array_map(fn (array $child): string => $this->plainText($child), $this->children($node)));
    }

    private function prefixLines(string $text, string $prefix): string
    {
        return implode("\n", array_map(
            static fn (string $line): string => rtrim($prefix.$line),
            explode("\n", $text),
        ));
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function children(array $node): array
    {
        $content = $node['content'] ?? [];

        return is_array($content) ? array_values(array_filter($content, 'is_array')) : [];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function attrs(array $node): array
    {
        $attrs = $node['attrs'] ?? [];

        return is_array($attrs) ? $attrs : [];
    }
}
