<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Constants\MediaConst;
use App\Enums\AuditEvent;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\Evidence;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * MEDIA-08: rows deleted longer than the trash keeps them are removed, with their links to evidence.
 */
final class PruneTrashCommandTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    public function test_rows_past_the_trash_are_removed_and_recent_ones_kept(): void
    {
        $this->useFakeStorage();
        $owner = $this->loginAsOwner(AdminMst::factory()->create());
        $old = $this->uploadFile($owner, 'old.pdf');
        $recent = $this->uploadFile($owner, 'recent.pdf');
        $live = $this->uploadFile($owner, 'live.pdf');
        $this->call('POST', '/api/admin/media/store-folder', ['folder' => '/', 'name' => 'Gone'], $owner)->assertOk();
        $folder = (int) MediaMgmt::query()->where('virtual_path', '/Gone')->value('id');
        $evidence = Evidence::factory()->create();
        $evidence->files()->attach([$old, $live]);
        $oldKey = (string) MediaMgmt::query()->findOrFail($old)->minio_object_key;

        $this->call('POST', '/api/admin/media/delete', ['ids' => [$old, $folder]], $owner)->assertOk();
        $this->travel(MediaConst::TRASH_DAYS)->days();
        $this->travel(1)->hours();
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$recent]], $owner)->assertOk();

        $this->artisan('media:prune-trash')->expectsOutput('Removed 2 expired row(s).')->assertSuccessful();

        $this->assertSame([$recent, $live], MediaMgmt::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame([$live], $evidence->files()->pluck('media_mgmt.id')->all());
        $this->assertContains($oldKey, $this->storage->purged);
        $this->assertTrue(AuditLog::query()->where(['auditable_type' => 'media', 'auditable_id' => $old, 'event' => AuditEvent::PURGED->value])->exists());

        // Nothing left to do on the next run
        $this->artisan('media:prune-trash')->expectsOutput('Removed 0 expired row(s).')->assertSuccessful();
    }
}
