<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * RFC-002 slice 5: admin search (REQ-002 US-5, ADR-0009).
 */
final class SearchApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const URL = '/api/admin/search';

    /** @var array<string, string> */
    private array $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        // Searching is a read, so a viewer may do it
        $this->viewer = $this->loginAs(AdminMst::factory()->create());
    }

    public function test_vietnamese_without_accents_finds_the_evidence(): void
    {
        $evidence = Evidence::factory()->create(['title' => 'Kỹ năng thiết kế database', 'summary' => null]);
        Evidence::factory()->create(['title' => 'Docker compose', 'summary' => 'Nginx']);

        $this->call('GET', self::URL, ['q' => 'ky nang'], $this->viewer)
            ->assertOk()
            ->assertJsonPath('data.match', 'exact')
            ->assertJsonPath('data.evidence', [['id' => $evidence->id, 'title' => 'Kỹ năng thiết kế database', 'snippet' => null]])
            ->assertJsonPath('data.skills', [])
            ->assertJsonPath('data.goals', []);
    }

    public function test_the_stored_search_vector_follows_an_edit(): void
    {
        $evidence = Evidence::factory()->create(['title' => 'Redis sentinel', 'summary' => null]);
        $evidence->update(['title' => 'Kafka partitions']);

        $this->call('GET', self::URL, ['q' => 'kafka'], $this->viewer)
            ->assertOk()
            ->assertJsonPath('data.match', 'exact')
            ->assertJsonPath('data.evidence.0.id', $evidence->id);
        // The old title is gone from both the vector and the trigram text
        $this->call('GET', self::URL, ['q' => 'sentinel'], $this->viewer)
            ->assertOk()
            ->assertJsonPath('data.evidence', []);
    }

    public function test_results_are_grouped_by_type_best_match_first(): void
    {
        $skill = Skill::factory()->create(['name' => 'PostgreSQL', 'category' => 'database', 'description' => 'Tối ưu truy vấn']);
        LearningGoal::factory()->for($skill)->create(['target_level' => SkillLevelEnum::INDEPENDENT, 'note' => null]);
        $weak = Evidence::factory()->create(['title' => 'Backup', 'summary' => 'Có nhắc PostgreSQL một lần']);
        $strong = Evidence::factory()->create(['title' => 'PostgreSQL index', 'summary' => 'Tối ưu PostgreSQL bằng index, truy vấn PostgreSQL']);

        $response = $this->call('GET', self::URL, ['q' => 'postgresql'], $this->viewer)->assertOk();

        $response->assertJsonPath('data.skills.0.id', $skill->id)
            ->assertJsonPath('data.goals.0.title', 'PostgreSQL → Independent')
            ->assertJsonPath('data.evidence.0.id', $strong->id)
            ->assertJsonPath('data.evidence.1.id', $weak->id);
    }

    public function test_a_title_match_ranks_above_a_summary_match(): void
    {
        // BUG-04: the summary repeats the words, the title only has them once
        $inSummary = Evidence::factory()->create(['title' => 'Ghi chú tuần 41', 'summary' => 'Kỹ năng mềm, kỹ năng đọc code, kỹ năng viết']);
        $inTitle = Evidence::factory()->create(['title' => 'Kỹ năng thiết kế database', 'summary' => null]);

        $this->call('GET', self::URL, ['q' => 'ky nang'], $this->viewer)
            ->assertOk()
            ->assertJsonPath('data.evidence.0.id', $inTitle->id)
            ->assertJsonPath('data.evidence.1.id', $inSummary->id);
    }

    public function test_last_word_is_a_prefix_while_typing(): void
    {
        $skill = Skill::factory()->create(['name' => 'Kiểm thử tự động', 'description' => null]);

        $this->call('GET', self::URL, ['q' => 'kiem th'], $this->viewer)
            ->assertJsonPath('data.match', 'exact')
            ->assertJsonPath('data.skills.0.id', $skill->id);
    }

    public function test_a_typo_falls_back_to_fuzzy_matching(): void
    {
        $evidence = Evidence::factory()->create(['title' => 'PostgreSQL partitioning', 'summary' => null]);

        $this->call('GET', self::URL, ['q' => 'postgersql'], $this->viewer)
            ->assertOk()
            ->assertJsonPath('data.match', 'fuzzy')
            ->assertJsonPath('data.evidence.0.id', $evidence->id);
    }

    public function test_nothing_found_is_an_empty_result_not_an_error(): void
    {
        Evidence::factory()->create(['title' => 'Redis', 'summary' => null]);

        foreach (['zzqxj', "'); drop table skill; --", '!! &|'] as $q) {
            $this->call('GET', self::URL, ['q' => $q], $this->viewer)
                ->assertOk()
                ->assertJsonPath('data.skills', [])
                ->assertJsonPath('data.goals', [])
                ->assertJsonPath('data.evidence', []);
        }
        $this->assertDatabaseCount('skill', 0);
    }

    public function test_query_length_is_validated_and_a_session_is_required(): void
    {
        $this->call('GET', self::URL, ['q' => 'a'], $this->viewer)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('GET', self::URL, ['q' => str_repeat('a', 101)], $this->viewer)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $this->flushSession();
        $this->getJson(self::URL.'?q=redis')->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }
}
