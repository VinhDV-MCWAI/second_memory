<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Enums\PreviewKind;
use App\Models\Content\Article;
use App\Models\Media\MediaMgmt;
use App\Repositories\Content\ArticleRepository;
use App\Services\Media\MediaService;
use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Normalizer\SlugNormalizer;
use Throwable;

/**
 * Markdown of articles and category descriptions → HTML the pages print as is (ADR-0021, ADR-0022):
 * GitHub flavour, raw HTML escaped, unsafe links dropped, headings with anchors. The wiki syntax of
 * WikiSyntax becomes HTML built here: the writer's text never turns into raw HTML.
 *
 * Embedded articles follow the public rule (a private one shows nothing), at most
 * ContentConst::EMBED_DEPTH_MAX levels deep, and a loop stops with a note. On the public site a file
 * is named by its key in `article_media`; the admin preview signs a short link instead.
 *
 * @phpstan-type TocEntry array{level: int, id: string, text: string}
 */
final class MarkdownRenderer
{
    public const MODE_PUBLIC = 'public';

    public const MODE_PREVIEW = 'preview';

    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly MediaService $mediaService,
    ) {}

    /**
     * @param  int|null  $articleId  owner of the embedded files' public keys; null for a description or a preview
     * @return array{html: string, toc: list<TocEntry>}
     */
    public function render(string $markdown, string $mode = self::MODE_PUBLIC, ?int $articleId = null): array
    {
        return $this->document($markdown, $mode, $articleId, [], '');
    }

    public function toHtml(string $markdown): string
    {
        return $this->render($markdown)['html'];
    }

    /**
     * @param  list<string>  $chain  slugs of the articles this one is embedded in, outermost first
     * @return array{html: string, toc: list<TocEntry>}
     */
    private function document(string $markdown, string $mode, ?int $articleId, array $chain, string $idPrefix): array
    {
        // Letters and digits only, so CommonMark leaves the markers as they are
        $nonce = 'wk'.bin2hex(random_bytes(6));
        $blocks = [];
        $links = [];
        $prepared = WikiSyntax::rewrite(
            $markdown,
            function (string $target, ?string $heading, ?string $text) use (&$blocks, $nonce, $mode, $articleId, $chain): string {
                $blocks[] = str_starts_with($target, WikiSyntax::FILE_PREFIX)
                    ? $this->file(substr($target, strlen(WikiSyntax::FILE_PREFIX)), $text, $mode, $articleId)
                    : $this->embed($target, $heading, $mode, $chain);

                return $nonce.'b'.(count($blocks) - 1).'x';
            },
            function (string $target, ?string $heading, ?string $text) use (&$links, $nonce): string {
                $links[] = $this->link($target, $heading, $text);

                return $nonce.'l'.(count($links) - 1).'x';
            },
        );

        $html = Str::markdown($prepared, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'id_prefix' => $idPrefix,
                'fragment_prefix' => $idPrefix,
                'apply_id_to_heading' => true,
                'insert' => 'after',
                'symbol' => '#',
                'title' => '',
                'aria_hidden' => true,
            ],
        ], [new HeadingPermalinkExtension]);

        foreach ($links as $index => $link) {
            $html = str_replace("{$nonce}l{$index}x", $link, $html);
        }
        // Read before the embedded articles go in: their headings belong to them
        $toc = self::toc($html);
        foreach ($blocks as $index => $block) {
            $html = str_replace(["<p>{$nonce}b{$index}x</p>", "{$nonce}b{$index}x"], $block, $html);
        }

        return ['html' => $html, 'toc' => $toc];
    }

    private function link(string $slug, ?string $heading, ?string $text): string
    {
        $article = $this->visible($slug);
        $label = e($text ?? $article->title ?? $slug);
        if ($article === null) {
            return $label;
        }
        $href = ContentConst::PUBLIC_ARTICLE_PATH.rawurlencode($article->slug).($heading === null ? '' : '#'.rawurlencode(self::anchor($heading)));

        return '<a href="'.e($href).'">'.$label.'</a>';
    }

    /**
     * @param  list<string>  $chain
     */
    private function embed(string $slug, ?string $heading, string $mode, array $chain): string
    {
        if (in_array($slug, $chain, true)) {
            return self::note(__('messages.embed_loop', ['slug' => $slug]));
        }
        if (count($chain) >= ContentConst::EMBED_DEPTH_MAX) {
            return self::note(__('messages.embed_too_deep'));
        }
        $article = $this->visible($slug);
        if ($article === null) {
            // The public page must not hint that a private article exists
            return $mode === self::MODE_PREVIEW ? self::note(__('messages.embed_hidden', ['slug' => $slug])) : '';
        }
        $markdown = $heading === null ? $article->body : WikiSyntax::section($article->body, $heading);
        if ($markdown === null) {
            return $mode === self::MODE_PREVIEW ? self::note(__('messages.embed_no_section', ['heading' => $heading])) : '';
        }

        $inner = $this->document($markdown, $mode, $article->id, [...$chain, $slug], $article->slug)['html'];
        $source = ContentConst::PUBLIC_ARTICLE_PATH.rawurlencode($article->slug);

        return '<section class="embed"><p class="embed-source"><a href="'.e($source).'">'.e($article->title).'</a></p>'.$inner.'</section>';
    }

    private function file(string $id, ?string $caption, string $mode, ?int $articleId): string
    {
        $file = ctype_digit($id) ? MediaMgmt::query()->available()->find((int) $id) : null;
        $url = $file === null ? null : $this->fileUrl($file, $mode, $articleId);
        if ($file === null || $url === null) {
            return $mode === self::MODE_PREVIEW ? self::note(__('messages.embed_file_missing', ['id' => $id])) : '';
        }

        $name = e($caption ?? $file->original_name);
        $src = e($url);

        return match (PreviewKind::forFile($file->extension, $file->size)) {
            PreviewKind::IMAGE => '<figure class="embed-file"><img src="'.$src.'" alt="'.$name.'" loading="lazy"><figcaption>'.$name.'</figcaption></figure>',
            PreviewKind::VIDEO => '<figure class="embed-file"><video src="'.$src.'" controls preload="metadata"></video><figcaption>'.$name.'</figcaption></figure>',
            PreviewKind::AUDIO => '<figure class="embed-file"><audio src="'.$src.'" controls preload="metadata"></audio><figcaption>'.$name.'</figcaption></figure>',
            default => '<p class="embed-file"><a href="'.$src.'" target="_blank" rel="noopener">📎 '.$name.'</a></p>',
        };
    }

    /**
     * Public: the site's `/files/{key}` (the key of this article's `article_media` row). Preview: a
     * short-lived signed link, or null when the admin may not open the file.
     */
    private function fileUrl(MediaMgmt $file, string $mode, ?int $articleId): ?string
    {
        if ($mode === self::MODE_PUBLIC) {
            $key = $articleId === null ? null : $this->articles->embeddedFileKey($articleId, $file->id);

            return $key === null ? null : ContentConst::PUBLIC_FILE_PATH.$key;
        }
        try {
            return $this->mediaService->downloadUrl($file->id, PreviewKind::forFile($file->extension, $file->size) !== null)['url'];
        } catch (Throwable) {
            return null;
        }
    }

    private function visible(string $slug): ?Article
    {
        return Article::query()->visible()->where('article.slug', $slug)->first();
    }

    private static function note(string $text): string
    {
        return '<p class="embed-note">'.e($text).'</p>';
    }

    /**
     * The anchor a heading gets: the same rule as the heading permalinks (lower case, spaces → dashes).
     */
    private static function anchor(string $heading): string
    {
        return (new SlugNormalizer)->normalize($heading);
    }

    /**
     * Headings of level 2 and 3.
     *
     * @return list<TocEntry>
     */
    private static function toc(string $html): array
    {
        preg_match_all('#<h([23]) id="([^"]+)">(.*?)</h\1>#s', $html, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m): array => [
            'level' => (int) $m[1],
            'id' => html_entity_decode($m[2]),
            'text' => trim(html_entity_decode(strip_tags((string) preg_replace('#<a [^>]*class="heading-permalink"[^>]*>.*?</a>#s', '', $m[3])))),
        ], $matches);
    }
}
