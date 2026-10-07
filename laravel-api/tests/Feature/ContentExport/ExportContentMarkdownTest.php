<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Management\CategoryMgmt;
use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Management\EntryMgmt;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

final class ExportContentMarkdownTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/content-export-'.uniqid();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_exports_linked_content_with_front_matter(): void
    {
        $intro = EntryDescriptionMgmt::factory()->create([
            'title' => 'Intro',
            'summary' => 'Why it matters',
            'article' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello']]]]],
        ]);
        $details = EntryDescriptionMgmt::factory()->create(['title' => 'Details', 'summary' => '', 'article' => 'Plain body']);
        $entry = EntryMgmt::factory()->create([
            'name' => 'Docker basics',
            'slug' => 'docker-basics',
            'layout_structure' => [['entry_desc_id' => $intro->id, 'children' => [['entry_desc_id' => $details->id]]]],
        ]);
        CategoryMgmt::factory()->create([
            'name' => 'DevOps',
            'slug' => 'devops',
            'description' => 'Infra notes',
            'layout_structure' => [['entry_mgmt_id' => $entry->id]],
        ]);

        $this->artisan('content:export-markdown', ['--path' => $this->dir])->assertSuccessful();

        $file = file_get_contents($this->dir.'/devops/docker-basics.md');
        $this->assertStringContainsString('title: "Docker basics"', $file);
        $this->assertStringContainsString('categories: ["DevOps"]', $file);
        $this->assertStringContainsString('source: "entry_mgmt:'.$entry->id.'"', $file);
        $this->assertStringContainsString("## Intro\n\n> Why it matters\n\nHello", $file);
        $this->assertStringContainsString("## Details\n\nPlain body", $file);
        $this->assertLessThan(strpos($file, '## Details'), strpos($file, '## Intro'));

        $index = file_get_contents($this->dir.'/devops/_index.md');
        $this->assertStringContainsString('Infra notes', $index);
        $this->assertStringContainsString('[[docker-basics|Docker basics]]', $index);
    }

    public function test_unreferenced_rows_are_kept_and_deleted_rows_skipped(): void
    {
        EntryMgmt::factory()->create(['slug' => 'orphan-entry']);
        $loose = EntryDescriptionMgmt::factory()->create(['title' => 'Loose note']);
        EntryMgmt::factory()->create(['slug' => 'gone', 'is_delete' => true]);

        $this->artisan('content:export-markdown', ['--path' => $this->dir])->assertSuccessful();

        $this->assertFileExists($this->dir.'/_uncategorized/orphan-entry.md');
        $this->assertFileExists($this->dir.'/_unlinked/'.$loose->id.'-loose-note.md');
        $this->assertFileDoesNotExist($this->dir.'/_uncategorized/gone.md');

        $this->artisan('content:export-markdown', ['--path' => $this->dir, '--include-deleted' => true])->assertSuccessful();
        $this->assertFileExists($this->dir.'/_uncategorized/gone.md');
    }

    public function test_running_twice_changes_nothing(): void
    {
        $entry = EntryMgmt::factory()->create(['slug' => 'stable']);
        CategoryMgmt::factory()->create(['slug' => 'cat', 'layout_structure' => [['entry_mgmt_id' => $entry->id]]]);

        $this->artisan('content:export-markdown', ['--path' => $this->dir])->assertSuccessful();
        $before = $this->snapshot();

        $this->artisan('content:export-markdown', ['--path' => $this->dir])
            ->expectsOutputToContain('Exported to')
            ->assertSuccessful();

        $this->assertSame($before, $this->snapshot());
    }

    /**
     * @return array<string, string>
     */
    private function snapshot(): array
    {
        $files = [];
        foreach ((new Filesystem)->allFiles($this->dir) as $file) {
            $files[$file->getRelativePathname()] = $file->getContents();
        }
        ksort($files);

        return $files;
    }
}
