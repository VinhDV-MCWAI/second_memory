<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * The admin search finds articles and categories (ADR-0022), private ones too. Files: SearchFilesApiTest.
 */
final class SearchContentApiTest extends TestCase
{
    use AuthenticatesAdmins;

    public function test_articles_and_categories_are_found_private_ones_included(): void
    {
        $group = ContentCategory::factory()->create(['name' => 'Kỹ năng']);
        $category = ContentCategory::factory()->in($group)->private()->create(['name' => 'PostgreSQL']);
        $article = Article::factory()->private()->create(['title' => 'Chỉ mục PostgreSQL', 'summary' => str_repeat('a ', 200)]);

        $response = $this->call('GET', '/api/admin/search', ['q' => 'postgresql'], $this->loginAs(AdminMst::factory()->create()))->assertOk()
            ->assertJsonPath('data.match', 'exact')
            ->assertJsonPath('data.categories', [['id' => $category->id, 'name' => 'PostgreSQL', 'slug' => $category->slug, 'group' => 'Kỹ năng', 'is_public' => false]])
            ->assertJsonPath('data.articles.0.id', $article->id)
            ->assertJsonPath('data.articles.0.is_public', false)
            // A viewer has no Files permission
            ->assertJsonPath('data.files', []);
        $this->assertLessThanOrEqual(163, mb_strlen((string) $response->json('data.articles.0.snippet')));
    }
}
