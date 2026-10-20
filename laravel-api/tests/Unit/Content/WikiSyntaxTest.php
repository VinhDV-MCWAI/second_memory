<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use App\Services\Content\WikiSyntax;
use PHPUnit\Framework\TestCase;

final class WikiSyntaxTest extends TestCase
{
    public function test_blocks_and_links_are_rewritten_outside_code_only(): void
    {
        $markdown = "Xem [[a]], [[b#Mục|chữ]] và `[[c]]`.\n\n![[d#Phần]]\n\n```\n![[e]]\n[[f]]\n```\n  ![[file:7|Ảnh]]  ";

        $blocks = [];
        $links = [];
        $out = WikiSyntax::rewrite(
            $markdown,
            function (string $target, ?string $heading, ?string $text) use (&$blocks): string {
                $blocks[] = [$target, $heading, $text];

                return 'BLOCK';
            },
            function (string $target, ?string $heading, ?string $text) use (&$links): string {
                $links[] = [$target, $heading, $text];

                return 'LINK';
            },
        );

        $this->assertSame([['d', 'Phần', null], ['file:7', null, 'Ảnh']], $blocks);
        $this->assertSame([['a', null, null], ['b', 'Mục', 'chữ']], $links);
        $this->assertStringContainsString('Xem LINK, LINK và `[[c]]`.', $out);
        $this->assertStringContainsString("```\n![[e]]\n[[f]]\n```", $out);
    }

    public function test_file_ids_come_once_in_order_and_only_from_block_lines(): void
    {
        $markdown = "![[file:3]]\ntext ![[file:4]] inline\n![[file:2|x]]\n![[file:3]]\n~~~\n![[file:9]]\n~~~\n![[file:abc]]";

        $this->assertSame([3, 2], WikiSyntax::fileIds($markdown));
    }

    public function test_a_section_runs_to_the_next_heading_of_the_same_level(): void
    {
        $markdown = "# Sách\n\n## Cài đặt\n\nBước 1\n\n### Chi tiết\n\nA\n\n```\n## Không phải tiêu đề\n```\n\n## Hàng đợi\n\nB";

        $this->assertSame("## Cài đặt\n\nBước 1\n\n### Chi tiết\n\nA\n\n```\n## Không phải tiêu đề\n```\n", WikiSyntax::section($markdown, ' cài ĐẶT '));
        $this->assertSame("## Hàng đợi\n\nB", WikiSyntax::section($markdown, 'Hàng đợi'));
        $this->assertNull(WikiSyntax::section($markdown, 'Không có'));
    }
}
