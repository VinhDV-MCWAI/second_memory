<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Constants\CommonVal;
use App\Constants\LedgerConst;
use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use Tests\TestCase;

/**
 * RFC-002 slice 5: public read-only API (REQ-002 US-3).
 */
final class PublicSkillApiTest extends TestCase
{
    private const BASE = '/api/public/skills';

    private Skill $laravel;

    private Evidence $publicEvidence;

    protected function setUp(): void
    {
        parent::setUp();

        // The US-3 scenario: public "Laravel" at level 3 with one public and one private evidence,
        // private "Rust", an open goal for "Laravel"
        $this->laravel = Skill::factory()->public()->create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
        $this->laravel->levels()->create(['level' => 3, 'changed_on' => now()->toDateString(), 'reason' => 'SECRET-REASON']);
        $this->laravel->update(['current_level' => SkillLevelEnum::INDEPENDENT]);
        $this->laravel->tags()->attach(Tag::factory()->create(['name' => 'php'])->id);

        $this->publicEvidence = Evidence::factory()->public()->create(['title' => 'Audit log PR', 'occurred_on' => '2026-10-07']);
        $private = Evidence::factory()->create(['title' => 'PRIVATE-EVIDENCE']);
        $hidden = Evidence::factory()->public()->fromObsidian()->create(['title' => 'UNPUBLISHED-NOTE', 'unpublished_at' => now()]);
        $this->laravel->evidence()->attach([$this->publicEvidence->id, $private->id, $hidden->id]);

        Skill::factory()->create(['name' => 'Rust', 'slug' => 'rust']);
        LearningGoal::factory()->for($this->laravel)->create(['target_level' => SkillLevelEnum::CAN_TEACH, 'note' => 'SECRET-GOAL']);
    }

    public function test_list_shows_public_skills_only_without_login(): void
    {
        $response = $this->getJson(self::BASE);

        $response->assertOk()
            ->assertJsonPath('data.data', [[
                'name' => 'Laravel',
                'slug' => 'laravel',
                'category' => 'backend',
                'current_level' => 3,
                'current_level_label' => 'Independent',
                'tags' => ['php'],
            ]]);
        $this->assertStringNotContainsString('Rust', $response->getContent() ?: '');
    }

    public function test_detail_shows_history_dates_and_public_evidence_only(): void
    {
        $response = $this->getJson(self::BASE.'/laravel');

        $response->assertOk()
            ->assertJsonPath('data.data.history.0', ['level' => 3, 'level_label' => 'Independent', 'changed_on' => now()->toDateString()])
            ->assertJsonCount(2, 'data.data.history')
            ->assertJsonPath('data.data.evidence', [[
                'type' => 'pr',
                'title' => 'Audit log PR',
                'url' => $this->publicEvidence->url,
                'occurred_on' => '2026-10-07',
                'summary' => $this->publicEvidence->summary,
            ]]);

        $body = $response->getContent() ?: '';
        foreach (['SECRET-REASON', 'SECRET-GOAL', 'PRIVATE-EVIDENCE', 'UNPUBLISHED-NOTE', 'Rust', '"id"', 'is_public'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $body);
        }
    }

    public function test_private_and_missing_skills_give_the_same_404(): void
    {
        $private = $this->getJson(self::BASE.'/rust');
        $missing = $this->getJson(self::BASE.'/does-not-exist');

        $private->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $missing->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->assertSame($missing->json(), $private->json());
    }

    public function test_writes_are_not_routed(): void
    {
        $this->postJson(self::BASE, ['name' => 'X'])->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
        $this->deleteJson(self::BASE.'/laravel')->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
    }

    public function test_requests_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < LedgerConst::PUBLIC_RATE_PER_MINUTE; $i++) {
            $this->getJson(self::BASE)->assertOk();
        }

        $this->getJson(self::BASE)->assertStatus(CommonVal::HTTP_TOO_MANY_REQUESTS)->assertHeader('Retry-After');
    }
}
