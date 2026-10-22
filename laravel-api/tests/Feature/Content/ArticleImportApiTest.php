<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Models\Audit\AuditLog;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * ADR-0022: idempotent sync of published Obsidian notes into articles, with parents, categories
 * and `[[links]]` named the Obsidian way.
 */
final class ArticleImportApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const URI = '/api/admin/article/import';

    private AdminMst $admin;

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminMst::factory()->create();
        $this->owner = $this->loginAsOwner($this->admin);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function note(string $key, array $overrides = []): array
    {
        return [
            'external_key' => $key,
            'title' => 'Hàng đợi Laravel',
            'summary' => 'Chạy việc nền.',
            'body' => "## Retry\n\nThử lại khi lỗi.",
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $notes
     */
    private function import(array $notes, bool $dryRun = false): TestResponse
    {
        return $this->call('POST', self::URI, ['notes' => $notes, 'dry_run' => $dryRun], $this->owner);
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, hidden: int}
     */
    private function counts(TestResponse $response): array
    {
        $response->assertOk();

        return [
            'created' => $response->json('data.created'),
            'updated' => $response->json('data.updated'),
            'unchanged' => $response->json('data.unchanged'),
            'hidden' => $response->json('data.hidden'),
        ];
    }

    /** The error of `notes.{n}.parent`: the keys hold dots, so no JSON path reaches them */
    private function parentError(TestResponse $response, int $position): ?string
    {
        $response->assertUnprocessable();

        return $response->json('error.messages')["notes.{$position}.parent"][0] ?? null;
    }

    private function article(string $key): Article
    {
        return Article::query()->with('categories')->where('external_key', $key)->sole();
    }

    public function test_a_published_note_becomes_a_public_article_in_its_categories(): void
    {
        $queue = ContentCategory::factory()->create(['name' => 'Hàng đợi']);
        Article::factory()->create(['slug' => 'hang-doi-laravel']);

        $response = $this->import([$this->note('dev/Queue.md', ['categories' => ['hàng đợi', 'Kotlin', 'kotlin']])]);

        $this->assertSame(['created' => 1, 'updated' => 0, 'unchanged' => 0, 'hidden' => 0], $this->counts($response));
        // Unknown categories are reported, never created
        $response->assertJsonPath('data.unknown_categories', ['Kotlin']);
        $this->assertSame(1, ContentCategory::query()->count());

        $article = $this->article('dev/Queue.md');
        $this->assertTrue($article->is_public);
        $this->assertSame('hang-doi-laravel-2', $article->slug);
        $this->assertSame("## Retry\n\nThử lại khi lỗi.", $article->body);
        $this->assertSame([$queue->id], $article->categories->modelKeys());
    }

    public function test_writes_are_audited_with_the_admin_as_actor_and_marked_as_imported(): void
    {
        $this->import([$this->note('dev/Queue.md')])->assertOk();

        $created = AuditLog::query()->where(['auditable_type' => 'article', 'event' => 'created'])->sole();
        $this->assertSame($this->admin->id, $created->admin_mst_id);
        $this->assertSame(ContentConst::IMPORT_AUDIT_VIA, $created->new_values['via']);
        $this->assertSame('dev/Queue.md', $created->new_values['external_key']);
    }

    public function test_a_second_run_changes_nothing_and_a_dry_run_writes_nothing(): void
    {
        $notes = [$this->note('dev/Queue.md'), $this->note('dev/Retry.md', ['title' => 'Retry', 'parent' => 'dev/Queue.md', 'body' => 'Xem [[Queue]].'])];

        $this->assertSame(['created' => 2, 'updated' => 0, 'unchanged' => 0, 'hidden' => 0], $this->counts($this->import($notes, dryRun: true)));
        $this->assertSame(0, Article::query()->count());

        $this->import($notes)->assertOk();
        $updatedAt = $this->article('dev/Retry.md')->updated_at;
        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 2, 'hidden' => 0], $this->counts($this->import($notes)));
        $this->assertEquals($updatedAt, $this->article('dev/Retry.md')->updated_at);
        $this->assertSame(2, AuditLog::query()->where('auditable_type', 'article')->count());
    }

    public function test_a_changed_note_is_updated_but_keeps_its_slug_and_the_owners_choice_of_public(): void
    {
        $this->import([$this->note('dev/Queue.md')])->assertOk();
        $article = $this->article('dev/Queue.md');
        $article->update(['is_public' => false]);

        $response = $this->import([$this->note('dev/Queue.md', ['title' => 'Hàng đợi (sửa)', 'body' => 'Mới'])]);

        $this->assertSame(['created' => 0, 'updated' => 1, 'unchanged' => 0, 'hidden' => 0], $this->counts($response));
        $article->refresh();
        $this->assertSame('Hàng đợi (sửa)', $article->title);
        $this->assertSame('Mới', $article->body);
        $this->assertSame('hang-doi-laravel', $article->slug);
        $this->assertFalse($article->is_public);
    }

    public function test_a_note_gone_from_the_list_is_hidden_and_articles_made_in_the_admin_are_left_alone(): void
    {
        $manual = Article::factory()->create();
        $this->import([$this->note('dev/Queue.md'), $this->note('dev/Old.md', ['title' => 'Old'])])->assertOk();

        $response = $this->import([$this->note('dev/Queue.md')]);

        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 1, 'hidden' => 1], $this->counts($response));
        $this->assertFalse($this->article('dev/Old.md')->is_public);
        $this->assertTrue($manual->refresh()->is_public);
        // Hidden once: an empty vault later hides only what is still public
        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 0, 'hidden' => 1], $this->counts($this->import([])));
    }

    public function test_links_are_resolved_the_obsidian_way_and_the_rest_becomes_text(): void
    {
        Article::factory()->create(['slug' => 'viet-tay', 'title' => 'Viết tay']);
        $body = implode("\n", [
            'Xem [[Queue]], [[dev/queue.md#Retry|thử lại]] và [[viet-tay]].',
            '',
            '![[Queue#Retry]]',
            '',
            'Chưa đăng: [[Nháp|bản nháp]], [[Nháp khác]].',
            '',
            '![[diagram.png]]',
            '',
            '![[file:12]]',
            '',
            '```',
            '[[Queue]] trong code giữ nguyên',
            '```',
        ]);

        $response = $this->import([
            $this->note('dev/Queue.md'),
            $this->note('notes/Queue.md', ['title' => 'Queue khác']),
            $this->note('dev/Links.md', ['title' => 'Links', 'body' => $body]),
        ]);

        $response->assertOk()->assertJsonPath('data.unknown_links', ['Nháp', 'Nháp khác', 'diagram.png', 'file:12']);
        $this->assertSame(implode("\n", [
            // The note in the same folder wins over the other "Queue"
            'Xem [[hang-doi-laravel]], [[hang-doi-laravel#Retry|thử lại]] và [[viet-tay]].',
            '',
            '',
            '![[hang-doi-laravel#Retry]]',
            '',
            '',
            'Chưa đăng: bản nháp, Nháp khác.',
            '',
            '',
            'diagram.png',
            '',
            '',
            '',
            'file:12',
            '',
            '',
            '```',
            '[[Queue]] trong code giữ nguyên',
            '```',
        ]), $this->article('dev/Links.md')->body);
    }

    public function test_parents_are_written_first_and_follow_the_note(): void
    {
        $response = $this->import([
            $this->note('book/Chapter.md', ['title' => 'Chương', 'parent' => '[[Book]]']),
            $this->note('book/Book.md', ['title' => 'Sách']),
            $this->note('book/Section.md', ['title' => 'Mục', 'parent' => 'book/Chapter']),
        ]);
        $response->assertOk();

        $book = $this->article('book/Book.md');
        $chapter = $this->article('book/Chapter.md');
        $this->assertSame($book->id, $chapter->parent_id);
        $this->assertSame($chapter->id, $this->article('book/Section.md')->parent_id);

        // The section moves up to the book and goes last there
        $this->import([
            $this->note('book/Chapter.md', ['title' => 'Chương', 'parent' => '[[Book]]']),
            $this->note('book/Book.md', ['title' => 'Sách']),
            $this->note('book/Section.md', ['title' => 'Mục', 'parent' => 'Book.md']),
        ])->assertOk()->assertJsonPath('data.updated', 1);
        $section = $this->article('book/Section.md');
        $this->assertSame($book->id, $section->parent_id);
        $this->assertSame(2, $section->sort_order);
    }

    public function test_bad_parents_stop_the_import_before_anything_is_written(): void
    {
        $this->assertSame(Messages::E0052, $this->parentError($this->import([$this->note('a.md', ['parent' => 'missing'])]), 0));
        $this->assertSame(Messages::E0053, $this->parentError($this->import([
            $this->note('a.md', ['parent' => 'b']),
            $this->note('b.md', ['title' => 'B', 'parent' => 'a']),
        ]), 0));

        $chain = [$this->note('n1.md', ['title' => 'N1'])];
        for ($i = 2; $i <= ContentConst::ARTICLE_DEPTH_MAX + 1; $i++) {
            $chain[] = $this->note("n{$i}.md", ['title' => "N{$i}", 'parent' => 'n'.($i - 1)]);
        }
        $this->assertSame(Messages::E0050, $this->parentError($this->import($chain), ContentConst::ARTICLE_DEPTH_MAX));

        $this->assertSame(0, Article::query()->count());
    }

    public function test_validation(): void
    {
        $this->call('POST', self::URI, [], $this->owner)->assertUnprocessable()->assertJsonValidationErrors(['notes'], 'error.messages');
        $this->import([
            $this->note('a.md', ['title' => str_repeat('x', ContentConst::ARTICLE_TITLE_MAX + 1)]),
            $this->note('a.md'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['notes.0.title', 'notes.1.external_key'], 'error.messages');
    }

    public function test_a_viewer_session_is_forbidden(): void
    {
        $this->call('POST', self::URI, ['notes' => []], $this->loginAs(AdminMst::factory()->create()))->assertForbidden();
    }
}
