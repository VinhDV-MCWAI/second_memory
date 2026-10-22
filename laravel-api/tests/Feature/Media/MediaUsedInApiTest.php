<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\Content\Article;
use App\Models\Content\CategoryAttachment;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * The Files listing tells where each file shows (ADR-0022): categories attaching it, articles embedding it.
 */
final class MediaUsedInApiTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    public function test_each_file_lists_the_categories_and_articles_using_it(): void
    {
        $this->useFakeStorage();
        $owner = $this->loginAsOwner(AdminMst::factory()->create());
        $used = $this->uploadFile($owner, 'chung-chi.pdf');
        $this->uploadFile($owner, 'le.txt');
        $category = ContentCategory::factory()->create(['name' => 'Laravel']);
        // Attached twice to one category, listed once
        foreach ([0, 1] as $position) {
            CategoryAttachment::query()->create(['content_category_id' => $category->id, 'title' => 'PDF', 'media_id' => $used, 'position' => $position]);
        }
        $article = Article::factory()->create(['title' => 'Hàng đợi']);
        DB::table('article_media')->insert(['article_id' => $article->id, 'media_id' => $used]);

        $rows = collect($this->call('GET', '/api/admin/media/list', ['folder' => '/'], $owner)->assertOk()->json('data.data'))->keyBy('name');

        $this->assertSame([
            ['kind' => 'category', 'id' => $category->id, 'title' => 'Laravel'],
            ['kind' => 'article', 'id' => $article->id, 'title' => 'Hàng đợi'],
        ], $rows['chung-chi.pdf']['used_in']);
        $this->assertSame([], $rows['le.txt']['used_in']);
    }
}
