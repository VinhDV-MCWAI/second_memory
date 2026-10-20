<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Content\Article;
use App\Models\Master\AdminMst;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * Links, embedded articles and embedded files in articles (ADR-0022), on the public page and in the
 * editor's preview.
 */
final class ArticleEmbedTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_links_and_embedded_articles_and_sections_with_a_table_of_contents(): void
    {
        Article::factory()->create(['slug' => 'redis', 'title' => 'Redis', 'body' => "## Cài đặt\n\nChạy **redis**.\n\n## Giám sát\n\nXem bộ nhớ."]);
        $page = $this->public("## Mở đầu\n\nXem [[redis#Giám sát|phần giám sát]].\n\n![[redis#Cài đặt]]\n\n### Kết\n\nHết.");

        $html = (string) $page->json('data.data.body_html');
        $this->assertStringContainsString('<a href="/articles/redis#'.rawurlencode('giám-sát').'">phần giám sát</a>', $html);
        $this->assertStringContainsString('<section class="embed"><p class="embed-source"><a href="/articles/redis">Redis</a></p>', $html);
        $this->assertStringContainsString('Chạy <strong>redis</strong>.', $html);
        $this->assertStringNotContainsString('Xem bộ nhớ', $html);
        // Only the article's own headings, not the embedded ones
        $page->assertJsonPath('data.data.toc', [
            ['level' => 2, 'id' => 'mở-đầu', 'text' => 'Mở đầu'],
            ['level' => 3, 'id' => 'kết', 'text' => 'Kết'],
        ]);
    }

    public function test_private_articles_loops_and_depth_never_leak_or_hang(): void
    {
        Article::factory()->private()->create(['slug' => 'bi-mat', 'title' => 'Bí mật', 'body' => 'Nội dung kín']);
        Article::factory()->create(['slug' => 'a', 'body' => "A\n\n![[b]]"]);
        Article::factory()->create(['slug' => 'b', 'body' => "B\n\n![[a]]"]);
        Article::factory()->create(['slug' => 'd1', 'body' => "D1\n\n![[d2]]"]);
        Article::factory()->create(['slug' => 'd2', 'body' => "D2\n\n![[d3]]"]);
        Article::factory()->create(['slug' => 'd3', 'body' => "D3\n\n![[d4]]"]);
        Article::factory()->create(['slug' => 'd4', 'body' => 'D4']);

        $html = (string) $this->public("[[bi-mat]]\n\n![[bi-mat]]\n\n![[a]]\n\n![[d1]]")->json('data.data.body_html');

        $this->assertStringNotContainsString('Nội dung kín', $html);
        $this->assertStringNotContainsString('/articles/bi-mat', $html);
        $this->assertStringContainsString('bi-mat', $html, 'a link to a private article stays plain text');
        $this->assertStringContainsString(__('messages.embed_loop', ['slug' => 'a']), $html);
        $this->assertStringContainsString('D3', $html);
        $this->assertStringNotContainsString('D4', $html);
        $this->assertStringContainsString(__('messages.embed_too_deep'), $html);
    }

    public function test_embedded_files_are_named_by_a_key_of_their_own_and_open_while_the_article_shows(): void
    {
        $this->useFakeStorage();
        $photo = $this->uploadFile($this->owner, 'so-do.png');
        $pdf = $this->uploadFile($this->owner, 'bao-cao.pdf');
        $id = $this->store("Ảnh\n\n![[file:{$photo}|Sơ đồ]]\n\n![[file:{$pdf}]]");
        $keys = DB::table('article_media')->where('article_id', $id)->orderBy('media_id')->pluck('public_id', 'media_id');

        $html = (string) $this->getJson('/api/public/articles/'.Article::query()->findOrFail($id)->slug)->assertOk()->json('data.data.body_html');
        $this->assertStringContainsString('<img src="/files/'.$keys[$photo].'" alt="Sơ đồ" loading="lazy">', $html);
        $this->assertStringContainsString('href="/files/'.$keys[$pdf].'"', $html);
        $this->assertStringNotContainsString("file:{$photo}", $html);
        $this->getJson("/api/public/files/{$keys[$photo]}")->assertOk();

        // A file taken out of the body loses its key; the one kept keeps it
        $this->call('PUT', "/api/admin/article/update/{$id}", ['title' => 'Ảnh', 'body' => "![[file:{$photo}]]"], $this->owner)->assertOk();
        $this->assertSame([$photo => $keys[$photo]], DB::table('article_media')->where('article_id', $id)->pluck('public_id', 'media_id')->all());
        $this->getJson("/api/public/files/{$keys[$pdf]}")->assertNotFound();

        // A private article's files are not public
        Article::query()->whereKey($id)->update(['is_public' => false]);
        $this->getJson("/api/public/files/{$keys[$photo]}")->assertNotFound();
    }

    public function test_only_uploaded_files_can_be_embedded(): void
    {
        $this->call('POST', '/api/admin/article/store', ['title' => 'X', 'body' => '![[file:999999]]'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.body.0', Messages::E0041);
    }

    public function test_the_preview_signs_file_links_and_notes_what_the_public_page_hides(): void
    {
        $this->useFakeStorage();
        $photo = $this->uploadFile($this->owner, 'so-do.png');
        Article::factory()->private()->create(['slug' => 'nhap']);

        $preview = $this->call('POST', '/api/admin/article/preview', ['body' => "## Một\n\n![[file:{$photo}]]\n\n![[nhap]]"], $this->owner)->assertOk();

        $html = (string) $preview->json('data.html');
        $this->assertStringContainsString('X-Amz-Expires=', $html);
        $this->assertStringContainsString(__('messages.embed_hidden', ['slug' => 'nhap']), $html);
        $preview->assertJsonPath('data.toc.0.text', 'Một');
        $this->call('POST', '/api/admin/article/preview', ['body' => 'x'], $this->loginAs(AdminMst::factory()->create()))
            ->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    private function store(string $body): int
    {
        return (int) $this->call('POST', '/api/admin/article/store', ['title' => 'Bài '.uniqid(), 'body' => $body], $this->owner)->assertOk()->json('data');
    }

    private function public(string $body): TestResponse
    {
        $slug = Article::query()->findOrFail($this->store($body))->slug;

        return $this->getJson("/api/public/articles/{$slug}")->assertOk();
    }
}
