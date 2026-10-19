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
 * Categories of the public site's home page (ADR-0021).
 */
final class ContentCategoryApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/content-category';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_crud_with_a_slug_set_once_unique_names_and_audit(): void
    {
        $payload = ['name' => 'Thiết kế', 'description' => 'Phương án, ADR', 'sort_order' => 30];
        $id = $this->call('POST', self::BASE.'/store', $payload, $this->owner)->assertOk()->json('data');
        $this->call('POST', self::BASE.'/store', ['name' => 'THIẾT KẾ'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['name']]]);

        $this->call('PUT', self::BASE."/update/{$id}", [...$payload, 'name' => 'Thiết kế hệ thống', 'is_public' => false], $this->owner)->assertOk();
        Article::factory()->create(['content_category_id' => $id]);

        $this->call('GET', self::BASE.'/list', ['name' => 'HỆ THỐNG'], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.slug', 'thiet-ke')
            ->assertJsonPath('data.data.0.is_public', false)
            ->assertJsonPath('data.data.0.article_count', 1);
        $this->assertSame(2, AuditLog::query()->where(['auditable_type' => 'content_category', 'auditable_id' => $id])->count());
    }

    public function test_a_category_with_articles_cannot_be_deleted(): void
    {
        $empty = ContentCategory::factory()->create();
        $article = Article::factory()->create();

        $this->call('POST', self::BASE.'/delete', ['ids' => [$empty->id, $article->content_category_id]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['ids']]]);
        $this->assertDatabaseHas('content_category', ['id' => $empty->id]);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$empty->id]], $this->owner)->assertOk();
        $this->assertDatabaseMissing('content_category', ['id' => $empty->id]);
    }

    public function test_the_viewer_reads_but_cannot_write(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk();
        $this->call('POST', self::BASE.'/store', ['name' => 'QA'], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }
}
