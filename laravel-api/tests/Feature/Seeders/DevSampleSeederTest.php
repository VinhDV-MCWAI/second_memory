<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Ledger\SkillLevel;
use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use Database\Seeders\DevSampleSeeder;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

final class DevSampleSeederTest extends TestCase
{
    use AuthenticatesAdmins;

    public function test_running_twice_creates_the_sample_data_once(): void
    {
        $this->seed(DevSampleSeeder::class);
        $counts = $this->counts();
        $this->seed(DevSampleSeeder::class);

        $this->assertSame($counts, $this->counts());
        $this->assertSame(5, $counts['skill']);
        $this->assertSame(6, $counts['evidence']);
    }

    public function test_each_skill_level_matches_its_newest_history_row(): void
    {
        $this->seed(DevSampleSeeder::class);

        foreach (Skill::query()->with('levels')->get() as $skill) {
            $newest = $skill->levels->sortBy([['changed_on', 'desc'], ['id', 'desc']])->first();
            $this->assertSame($skill->current_level, $newest?->level, $skill->slug);
        }
    }

    public function test_viewer_account_can_read_but_not_write(): void
    {
        $this->seed(DevSampleSeeder::class);
        $viewer = AdminMst::query()->where('user_name', DevSampleSeeder::VIEWER_USER_NAME)->firstOrFail();
        $this->assertSame(AdminRole::VIEWER, $viewer->role);

        $cookies = $this->loginAs($viewer, DevSampleSeeder::VIEWER_PASSWORD);

        $this->call('GET', '/api/admin/skill/list', [], $cookies)->assertOk();
        $this->call('POST', '/api/admin/tag/store', ['name' => 'go'], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'admin' => AdminMst::query()->count(),
            'tag' => Tag::query()->count(),
            'skill' => Skill::query()->count(),
            'skill_level' => SkillLevel::query()->count(),
            'evidence' => Evidence::query()->count(),
            'evidence_skill' => Evidence::query()->withCount('skills')->get()->sum('skills_count'),
            'goal' => LearningGoal::query()->count(),
        ];
    }
}
