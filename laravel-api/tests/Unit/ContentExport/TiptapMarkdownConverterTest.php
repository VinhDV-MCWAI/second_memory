<?php

declare(strict_types=1);

namespace Tests\Unit\ContentExport;

use App\Services\ContentExport\TiptapMarkdownConverter;
use PHPUnit\Framework\TestCase;

final class TiptapMarkdownConverterTest extends TestCase
{
    private TiptapMarkdownConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new TiptapMarkdownConverter;
    }

    public function test_null_and_plain_string(): void
    {
        $this->assertSame('', $this->converter->convert(null));
        $this->assertSame('plain text', $this->converter->convert("  plain text \n"));
    }

    public function test_headings_paragraphs_and_marks(): void
    {
        $doc = $this->doc(
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [$this->text('Title')]],
            ['type' => 'paragraph', 'content' => [
                $this->text('bold', ['bold']),
                $this->text(' and '),
                $this->text('code', ['code']),
                $this->text(' and '),
                ['type' => 'text', 'text' => 'link', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com']]]],
                ['type' => 'hardBreak'],
                $this->text('next line', ['italic', 'strike']),
            ]],
        );

        $this->assertSame(
            "## Title\n\n**bold** and `code` and [link](https://example.com)  \n~~*next line*~~",
            $this->converter->convert($doc),
        );
    }

    public function test_nested_lists(): void
    {
        $doc = $this->doc(
            ['type' => 'bulletList', 'content' => [
                $this->item('one'),
                ['type' => 'listItem', 'content' => [
                    ['type' => 'paragraph', 'content' => [$this->text('two')]],
                    ['type' => 'orderedList', 'attrs' => ['start' => 3], 'content' => [$this->item('a'), $this->item('b')]],
                ]],
            ]],
        );

        $this->assertSame("- one\n- two\n\n  3. a\n  4. b", $this->converter->convert($doc));
    }

    public function test_block_nodes(): void
    {
        $doc = $this->doc(
            ['type' => 'blockquote', 'content' => [['type' => 'paragraph', 'content' => [$this->text('quote')]]]],
            ['type' => 'codeBlock', 'attrs' => ['language' => 'php'], 'content' => [$this->text("echo 1;\necho 2;")]],
            ['type' => 'horizontalRule'],
            ['type' => 'image', 'attrs' => ['src' => 'https://cdn/x.png', 'alt' => 'diagram']],
            ['type' => 'video', 'attrs' => ['src' => 'https://cdn/v.mp4']],
        );

        $this->assertSame(
            "> quote\n\n```php\necho 1;\necho 2;\n```\n\n---\n\n![diagram](https://cdn/x.png)\n\n[video](https://cdn/v.mp4)",
            $this->converter->convert($doc),
        );
    }

    public function test_unknown_nodes_keep_their_text(): void
    {
        $doc = $this->doc(['type' => 'callout', 'content' => [['type' => 'paragraph', 'content' => [$this->text('kept')]]]]);

        $this->assertSame('kept', $this->converter->convert($doc));
    }

    /**
     * @param  array<string, mixed>  ...$nodes
     * @return array<string, mixed>
     */
    private function doc(array ...$nodes): array
    {
        return ['type' => 'doc', 'content' => $nodes];
    }

    /**
     * @param  list<string>  $marks
     * @return array<string, mixed>
     */
    private function text(string $text, array $marks = []): array
    {
        $node = ['type' => 'text', 'text' => $text];
        if ($marks !== []) {
            $node['marks'] = array_map(static fn (string $type): array => ['type' => $type], $marks);
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function item(string $text): array
    {
        return ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [$this->text($text)]]]];
    }
}
