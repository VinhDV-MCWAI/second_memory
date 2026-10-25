<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Constants\CommonVal;
use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Models\Audit\AuditLog;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Articles (ADR-0022): Markdown source, slug unique site-wide, many categories, a tree of sub-pages.
 */
final class ArticleApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/article';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_crud_with_site_wide_slugs_many_categories_and_audit(): void
    {
        [$design, $qa] = ContentCategory::factory()->count(2)->create();
        Article::factory()->create(['slug' => 'ke-hoach']);

        $payload = ['category_ids' => [$design->id, $qa->id], 'title' => 'Kế hoạch', 'summary' => 'Tóm tắt', 'body' => "## Mục\n\nNội dung"];
        $id = $this->call('POST', self::BASE.'/store', $payload, $this->owner)->assertOk()->json('data');
        $this->assertDatabaseHas('article', ['id' => $id, 'slug' => 'ke-hoach-2']);

        // The slug stays when the title changes; categories follow the list sent, and stay without one
        $this->call('PUT', self::BASE."/update/{$id}", [...$payload, 'title' => 'Kế hoạch test', 'category_ids' => [$qa->id], 'is_public' => false], $this->owner)->assertOk();
        $this->call('PUT', self::BASE."/update/{$id}", ['title' => 'Kế hoạch test', 'body' => 'Mới'], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['category_id' => $qa->id, 'title' => 'TEST'], $this->owner)->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.slug', 'ke-hoach-2')
            ->assertJsonPath('data.data.0.categories.0.id', $qa->id)
            ->assertJsonPath('data.data.0.is_public', false);
        $this->call('GET', self::BASE.'/list', ['category_id' => $design->id], $this->owner)->assertJsonCount(0, 'data.data');

        $this->call('POST', self::BASE.'/delete', ['ids' => [$id]], $this->owner)->assertOk();
        $this->assertSame(4, AuditLog::query()->where(['auditable_type' => 'article', 'auditable_id' => $id])->count());
    }

    public function test_the_list_filters_by_any_of_several_categories(): void
    {
        [$design, $qa, $ops, $sub] = ContentCategory::factory()->count(4)->create();
        $sub->update(['parent_id' => $design->id]);
        $inDesign = $this->store(['title' => 'Thiết kế', 'category_ids' => [$design->id]]);
        $inBoth = $this->store(['title' => 'Cả hai', 'category_ids' => [$design->id, $qa->id]]);
        $inQa = $this->store(['title' => 'Kiểm thử', 'category_ids' => [$qa->id]]);
        $this->store(['title' => 'Vận hành', 'category_ids' => [$ops->id]]);
        $this->store(['title' => 'Nhánh con', 'category_ids' => [$sub->id]]);

        // Each article once, and a category's sub-categories are not included
        $this->call('GET', self::BASE.'/list', ['category_ids' => [$design->id, $qa->id], 'sort_by' => 'id', 'sort_order' => 'asc'], $this->owner)->assertOk()
            ->assertJsonPath('data.data.*.id', [$inDesign, $inBoth, $inQa]);
        $this->call('GET', self::BASE.'/list', ['category_ids' => ['x']], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['category_ids.0']]]);
    }

    public function test_sub_pages_form_a_tree_in_their_own_order(): void
    {
        $book = $this->store(['title' => 'Laravel thực chiến']);
        $setup = $this->store(['title' => 'Cài đặt', 'parent_id' => $book]);
        $queues = $this->store(['title' => 'Hàng đợi', 'parent_id' => $book]);
        $redis = $this->store(['title' => 'Redis', 'parent_id' => $queues]);

        $this->call('GET', self::BASE.'/list', ['parent_id' => $book], $this->owner)->assertOk()
            ->assertJsonPath('data.data.*.id', [$setup, $queues])
            ->assertJsonPath('data.data.1.children_count', 1)
            ->assertJsonPath('data.data.1.parent_title', 'Laravel thực chiến');
        $this->call('GET', self::BASE.'/list', ['root' => 1], $this->owner)->assertJsonPath('data.data.*.id', [$book]);

        $this->call('PUT', self::BASE."/move/{$queues}", ['direction' => 'up'], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['parent_id' => $book], $this->owner)->assertJsonPath('data.data.*.id', [$queues, $setup]);

        // Moving a chapter under another book puts it last there
        $other = $this->store(['title' => 'Docker']);
        $this->call('PUT', self::BASE."/update/{$redis}", ['title' => 'Redis', 'body' => 'x', 'parent_id' => $other], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['parent_id' => $other], $this->owner)->assertJsonPath('data.data.*.id', [$redis]);
    }

    public function test_no_loops_and_at_most_ten_levels(): void
    {
        $ids = [$this->store(['title' => 'Cấp 1'])];
        for ($level = 2; $level <= ContentConst::ARTICLE_DEPTH_MAX; $level++) {
            $ids[] = $this->store(['title' => "Cấp {$level}", 'parent_id' => end($ids)]);
        }

        $this->call('POST', self::BASE.'/store', ['title' => 'Cấp 11', 'body' => 'x', 'parent_id' => end($ids)], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.parent_id.0', Messages::E0050);
        // Under itself or one of its own sub-pages
        foreach ([$ids[2], $ids[5]] as $parent) {
            $this->call('PUT', self::BASE."/update/{$ids[2]}", ['title' => 'Cấp 3', 'body' => 'x', 'parent_id' => $parent], $this->owner)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.parent_id.0', Messages::E0049);
        }
        // A subtree of 2 levels fits under level 8, not under level 9
        $branch = $this->store(['title' => 'Nhánh']);
        $this->store(['title' => 'Lá', 'parent_id' => $branch]);
        $this->call('PUT', self::BASE."/update/{$branch}", ['title' => 'Nhánh', 'body' => 'x', 'parent_id' => $ids[8]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.parent_id.0', Messages::E0050);
        $this->call('PUT', self::BASE."/update/{$branch}", ['title' => 'Nhánh', 'body' => 'x', 'parent_id' => $ids[7]], $this->owner)->assertOk();
    }

    public function test_an_article_with_sub_pages_goes_only_together_with_them(): void
    {
        $book = $this->store(['title' => 'Sách']);
        $chapter = $this->store(['title' => 'Chương', 'parent_id' => $book]);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$book]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.ids.0', Messages::E0051);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$book, $chapter]], $this->owner)->assertOk();
        $this->assertDatabaseCount('article', 0);
    }

    public function test_store_checks_the_fields(): void
    {
        $this->call('POST', self::BASE.'/store', ['category_ids' => [0], 'parent_id' => 0, 'title' => '', 'body' => ''], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['category_ids.0', 'parent_id', 'title', 'body']]]);
    }

    public function test_the_viewer_reads_but_cannot_write(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());
        $article = Article::factory()->create();

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk()->assertJsonPath('data.data.0.title', $article->title);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$article->id]], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('PUT', self::BASE."/move/{$article->id}", ['direction' => 'down'], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function store(array $payload): int
    {
        return (int) $this->call('POST', self::BASE.'/store', ['body' => 'Nội dung', ...$payload], $this->owner)->assertOk()->json('data');
    }
}
