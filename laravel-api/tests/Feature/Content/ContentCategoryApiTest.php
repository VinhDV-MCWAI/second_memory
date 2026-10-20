<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Audit\AuditLog;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * Groups and categories of the public site (ADR-0022).
 */
final class ContentCategoryApiTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

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
        $payload = ['name' => 'Thiết kế', 'description' => "## Phương án\n\nADR"];
        $id = $this->call('POST', self::BASE.'/store', $payload, $this->owner)->assertOk()->json('data');
        $this->call('POST', self::BASE.'/store', ['name' => 'THIẾT KẾ'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['name']]]);

        $this->call('PUT', self::BASE."/update/{$id}", [...$payload, 'name' => 'Thiết kế hệ thống', 'is_public' => false], $this->owner)->assertOk();
        Article::factory()->inCategory(ContentCategory::query()->findOrFail($id))->create();

        $this->call('GET', self::BASE.'/list', ['name' => 'HỆ THỐNG'], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.slug', 'thiet-ke')
            ->assertJsonPath('data.data.0.is_public', false)
            ->assertJsonPath('data.data.0.article_count', 1);
        $this->assertSame(2, AuditLog::query()->where(['auditable_type' => 'content_category', 'auditable_id' => $id])->count());
    }

    public function test_two_levels_listed_as_a_tree_and_new_rows_go_last(): void
    {
        $skills = $this->store(['name' => 'Kỹ năng']);
        $process = $this->store(['name' => 'Quy trình']);
        $docker = $this->store(['name' => 'Docker', 'parent_id' => $skills]);
        $laravel = $this->store(['name' => 'Laravel', 'parent_id' => $skills]);
        $design = $this->store(['name' => 'Thiết kế', 'parent_id' => $process]);

        $this->call('GET', self::BASE.'/list', [], $this->owner)->assertOk()
            ->assertJsonPath('data.data.*.id', [$skills, $docker, $laravel, $process, $design])
            ->assertJsonPath('data.data.0.children_count', 2)
            ->assertJsonPath('data.data.1.parent_name', 'Kỹ năng');
        $this->call('GET', self::BASE.'/list', ['root' => 1], $this->owner)->assertJsonPath('data.data.*.id', [$skills, $process]);

        // Not under a category, not under itself, and a group with categories stays a group
        foreach ([[$design, ['parent_id' => $docker]], [$skills, ['parent_id' => $skills]], [$skills, ['parent_id' => $process]]] as [$id, $change]) {
            $this->call('PUT', self::BASE."/update/{$id}", ['name' => "Tên {$id}", ...$change], $this->owner)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.parent_id.0', Messages::E0048);
        }

        // Moving to another group puts it last there
        $this->call('PUT', self::BASE."/update/{$docker}", ['name' => 'Docker', 'parent_id' => $process], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['parent_id' => $process], $this->owner)->assertJsonPath('data.data.*.id', [$design, $docker]);
    }

    public function test_the_arrows_swap_a_row_with_its_neighbour_without_touching_the_edit_date(): void
    {
        $group = $this->store(['name' => 'Kỹ năng']);
        [$a, $b, $c] = [$this->store(['name' => 'A', 'parent_id' => $group]), $this->store(['name' => 'B', 'parent_id' => $group]), $this->store(['name' => 'C', 'parent_id' => $group])];
        $updated = ContentCategory::query()->findOrFail($a)->updated_at;
        $this->travel(1)->minutes();

        $this->call('PUT', self::BASE."/move/{$c}", ['direction' => 'up'], $this->owner)->assertOk();
        $this->call('PUT', self::BASE."/move/{$a}", ['direction' => 'up'], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['parent_id' => $group], $this->owner)->assertJsonPath('data.data.*.id', [$a, $c, $b]);

        $this->assertEquals($updated, ContentCategory::query()->findOrFail($a)->updated_at);
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'content_category', 'auditable_id' => $c, 'event' => 'updated'])->count());
        $this->call('PUT', self::BASE."/move/{$a}", ['direction' => 'sideways'], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_attachments_are_links_or_files_kept_by_id_in_the_given_order(): void
    {
        $this->useFakeStorage();
        $file = $this->uploadFile($this->owner, 'chung-chi.pdf');
        $id = $this->store(['name' => 'AWS', 'attachments' => [
            ['title' => 'Chứng chỉ', 'media_id' => $file],
            ['title' => 'Hồ sơ', 'url' => 'https://example.com/profile', 'note' => 'Trang công khai'],
        ]]);

        $first = $this->call('GET', self::BASE.'/list', ['id' => $id], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.attachments.0.file.name', 'chung-chi.pdf')
            ->assertJsonPath('data.data.0.attachments.0.file.available', true)
            ->assertJsonPath('data.data.0.attachments.1.url', 'https://example.com/profile')
            ->json('data.data.0.attachments');
        $publicId = (string) DB::table('category_attachment')->where('id', $first[0]['id'])->value('public_id');

        // Reorder, keep the file by id (and its public key), drop the link
        $this->call('PUT', self::BASE."/update/{$id}", ['name' => 'AWS', 'attachments' => [
            ['id' => $first[0]['id'], 'title' => 'Chứng chỉ SAA', 'media_id' => $file],
        ]], $this->owner)->assertOk();
        $this->assertSame([$publicId], DB::table('category_attachment')->where('content_category_id', $id)->pluck('public_id')->all());

        // Without `attachments` they stay
        $this->call('PUT', self::BASE."/update/{$id}", ['name' => 'AWS'], $this->owner)->assertOk();
        $this->assertSame(1, DB::table('category_attachment')->where('content_category_id', $id)->count());
    }

    public function test_an_attachment_needs_a_link_or_an_uploaded_file(): void
    {
        $this->call('POST', self::BASE.'/store', ['name' => 'X', 'attachments' => [['title' => 'Trống']]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages', ['attachments.0.url' => [Messages::E0042]]);
        $this->call('POST', self::BASE.'/store', ['name' => 'X', 'attachments' => [['title' => 'Lạ', 'url' => 'javascript:alert(1)']]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('POST', self::BASE.'/store', ['name' => 'X', 'attachments' => [['title' => 'Không có', 'media_id' => 999999]]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.attachments.0', Messages::E0041);
    }

    public function test_a_group_with_categories_cannot_be_deleted_but_articles_only_lose_the_link(): void
    {
        $group = ContentCategory::factory()->create();
        $inside = ContentCategory::factory()->in($group)->create();
        $article = Article::factory()->inCategory($inside)->create();

        $this->call('POST', self::BASE.'/delete', ['ids' => [$group->id]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.ids.0', Messages::E0047);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$inside->id]], $this->owner)->assertOk();
        $this->assertDatabaseHas('article', ['id' => $article->id]);
        $this->assertDatabaseMissing('article_category', ['article_id' => $article->id]);

        // A group goes together with what is inside it
        $other = ContentCategory::factory()->in($group)->create();
        $this->call('POST', self::BASE.'/delete', ['ids' => [$group->id, $other->id]], $this->owner)->assertOk();
        $this->assertDatabaseCount('content_category', 0);
    }

    public function test_the_viewer_reads_but_cannot_write(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());
        $id = ContentCategory::factory()->create()->id;

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk();
        $this->call('POST', self::BASE.'/store', ['name' => 'QA'], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('PUT', self::BASE."/move/{$id}", ['direction' => 'up'], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function store(array $payload): int
    {
        return (int) $this->call('POST', self::BASE.'/store', $payload, $this->owner)->assertOk()->json('data');
    }
}
