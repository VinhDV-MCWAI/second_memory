<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

final class TagApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/tag';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_crud_with_case_insensitive_names_and_audit(): void
    {
        $id = $this->call('POST', self::BASE.'/store', ['name' => 'Laravel'], $this->owner)->assertOk()->json('data');
        $this->call('POST', self::BASE.'/store', ['name' => 'LARAVEL'], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $this->call('PUT', self::BASE."/update/{$id}", ['name' => 'laravel'], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['name' => 'LARA'], $this->owner)
            ->assertOk()->assertJsonPath('data.data.0.name', 'laravel');

        $this->assertSame(2, AuditLog::query()->where(['auditable_type' => 'tag', 'auditable_id' => $id])->count());
    }

    public function test_delete_unlinks_skills(): void
    {
        $tag = Tag::factory()->create();
        $skill = Skill::factory()->create();
        $skill->tags()->attach($tag->id);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$tag->id]], $this->owner)->assertOk();

        $this->assertDatabaseMissing('skill_tag', ['tag_id' => $tag->id]);
        $this->assertDatabaseHas('skill', ['id' => $skill->id]);
    }

    public function test_viewer_cannot_write(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('POST', self::BASE.'/store', ['name' => 'Go'], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }
}
