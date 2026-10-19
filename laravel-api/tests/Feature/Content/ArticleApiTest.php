<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Constants\CommonVal;
use App\Models\Audit\AuditLog;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Articles of the public site (ADR-0021): Markdown source, slug unique within the category.
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

    public function test_crud_keeps_slugs_unique_per_category_and_audits(): void
    {
        $design = ContentCategory::factory()->create();
        $qa = ContentCategory::factory()->create();
        Article::factory()->create(['content_category_id' => $qa->id, 'slug' => 'ke-hoach']);

        $payload = ['content_category_id' => $design->id, 'title' => 'Kế hoạch', 'summary' => 'Tóm tắt', 'body' => "## Mục\n\nNội dung"];
        $id = $this->call('POST', self::BASE.'/store', $payload, $this->owner)->assertOk()->json('data');
        $second = $this->call('POST', self::BASE.'/store', $payload, $this->owner)->assertOk()->json('data');
        $this->assertSame(['ke-hoach', 'ke-hoach-2'], Article::query()->whereKey([$id, $second])->orderBy('id')->pluck('slug')->all());

        // Moving to a category where "ke-hoach" is taken renames the slug
        $this->call('PUT', self::BASE."/update/{$id}", [...$payload, 'content_category_id' => $qa->id, 'is_public' => false], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['content_category_id' => $qa->id, 'title' => 'KẾ HOẠCH'], $this->owner)->assertOk()
            ->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $id);
        $this->assertDatabaseHas('article', ['id' => $id, 'content_category_id' => $qa->id, 'slug' => 'ke-hoach-2', 'is_public' => false]);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$id]], $this->owner)->assertOk();
        $this->assertSame(3, AuditLog::query()->where(['auditable_type' => 'article', 'auditable_id' => $id])->count());
    }

    public function test_store_checks_the_fields(): void
    {
        $this->call('POST', self::BASE.'/store', ['content_category_id' => 0, 'title' => '', 'body' => ''], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['content_category_id', 'title', 'body']]]);
    }

    public function test_the_viewer_reads_but_cannot_write(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());
        $article = Article::factory()->create();

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk()->assertJsonPath('data.data.0.title', $article->title);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$article->id]], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }
}
