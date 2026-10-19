<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\EvidenceSource;
use App\Enums\EvidenceType;
use App\Enums\GoalStatus;
use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Ledger\SkillLevel;
use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use App\Models\User\EndUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Development only (refuses production): a read-only viewer account, an end user of the public site and a small Skill Ledger (skills with a
 * level history, tags, evidence, goals) so the admin has something to click through.
 * Idempotent: rows are matched by a natural key and never duplicated or overwritten.
 */
class DevSampleSeeder extends Seeder
{
    public const VIEWER_USER_NAME = 'viewer';

    public const VIEWER_PASSWORD = '12345678';

    /** End user of the public site (ADR-0020), separate from the admin accounts */
    public const END_USER_NAME = 'demo';

    public const END_USER_PASSWORD = '12345678';

    private const TAGS = ['database', 'backend', 'devops', 'frontend', 'performance'];

    /**
     * Skills keyed by slug. `levels` is the history, oldest first, as [level, days ago, reason].
     *
     * @var array<string, array{name: string, category: string, description: string, is_public: bool, tags: list<string>, levels: list<array{SkillLevelEnum, int, string}>}>
     */
    private const SKILLS = [
        'postgresql' => [
            'name' => 'PostgreSQL',
            'category' => 'database',
            'description' => 'Thiết kế bảng, index, đọc query plan, full-text search.',
            'is_public' => true,
            'tags' => ['database', 'performance'],
            'levels' => [
                [SkillLevelEnum::LEARNING, 400, 'Bắt đầu học khi chuyển từ MySQL.'],
                [SkillLevelEnum::WITH_HELP, 250, 'Tự viết migration, còn cần tra cứu index.'],
                [SkillLevelEnum::INDEPENDENT, 60, 'Tự tối ưu được truy vấn tìm kiếm của Skill Ledger.'],
            ],
        ],
        'laravel' => [
            'name' => 'Laravel',
            'category' => 'backend',
            'description' => 'REST API, Sanctum, FormRequest, test với PostgreSQL thật.',
            'is_public' => true,
            'tags' => ['backend'],
            'levels' => [
                [SkillLevelEnum::WITH_HELP, 500, 'Dự án đầu tiên theo hướng dẫn.'],
                [SkillLevelEnum::INDEPENDENT, 200, 'Tự dựng toàn bộ API của Second Memory.'],
            ],
        ],
        'docker' => [
            'name' => 'Docker',
            'category' => 'devops',
            'description' => 'Docker Compose cho môi trường dev, image chạy non-root.',
            'is_public' => true,
            'tags' => ['devops'],
            'levels' => [
                [SkillLevelEnum::LEARNING, 300, 'Chạy container có sẵn.'],
                [SkillLevelEnum::WITH_HELP, 90, 'Tự viết Dockerfile nhiều stage.'],
            ],
        ],
        'react' => [
            'name' => 'React',
            'category' => 'frontend',
            'description' => 'Next.js App Router, TanStack Query, form với zod.',
            'is_public' => false,
            'tags' => ['frontend'],
            'levels' => [
                [SkillLevelEnum::LEARNING, 120, 'Làm trang quản trị đầu tiên.'],
            ],
        ],
        'terraform' => [
            'name' => 'Terraform',
            'category' => 'devops',
            'description' => 'Dựng môi trường bằng Docker provider.',
            'is_public' => false,
            'tags' => ['devops'],
            'levels' => [
                [SkillLevelEnum::LEARNING, 45, 'Thử dựng môi trường giống production.'],
            ],
        ],
    ];

    /**
     * Evidence keyed by URL. Imported notes carry an Obsidian vault path as external key.
     *
     * @var array<string, array{type: EvidenceType, title: string, days_ago: int, summary: string|null, is_public: bool, skills: list<string>, tags: list<string>, external_key?: string}>
     */
    private const EVIDENCE = [
        'https://github.com/example/second-memory/pull/101' => [
            'type' => EvidenceType::PR,
            'title' => 'Thêm full-text search cho bằng chứng',
            'days_ago' => 70,
            'summary' => 'Cột tsvector, index GIN và tìm gần đúng bằng pg_trgm.',
            'is_public' => true,
            'skills' => ['postgresql', 'laravel'],
            'tags' => ['database', 'performance'],
        ],
        'https://github.com/example/second-memory/blob/main/docs/adr/0004-sanctum-spa-auth.md' => [
            'type' => EvidenceType::ADR,
            'title' => 'Chọn đăng nhập bằng session Sanctum',
            'days_ago' => 210,
            'summary' => 'So sánh JWT tự viết với session cookie của Sanctum.',
            'is_public' => true,
            'skills' => ['laravel'],
            'tags' => ['backend'],
        ],
        'https://github.com/example/second-memory/issues/42' => [
            'type' => EvidenceType::INCIDENT,
            'title' => 'Container nginx giữ file cấu hình cũ',
            'days_ago' => 30,
            'summary' => 'Bind mount một file bị lệch inode sau khi sửa bằng sed -i.',
            'is_public' => false,
            'skills' => ['docker'],
            'tags' => ['devops'],
        ],
        'https://github.com/example/second-memory/pull/87' => [
            'type' => EvidenceType::PR,
            'title' => 'Trang quản trị skill với TanStack Query',
            'days_ago' => 110,
            'summary' => null,
            'is_public' => false,
            'skills' => ['react'],
            'tags' => ['frontend'],
        ],
        'https://notes.example.com/postgresql-query-plan' => [
            'type' => EvidenceType::NOTE,
            'title' => 'Ghi chú: đọc EXPLAIN ANALYZE',
            'days_ago' => 15,
            'summary' => 'Seq scan, index scan, bitmap scan và khi nào planner chọn từng loại.',
            'is_public' => true,
            'skills' => ['postgresql'],
            'tags' => ['database', 'performance'],
            'external_key' => 'notes/postgresql-query-plan.md',
        ],
        'https://notes.example.com/terraform-docker-provider' => [
            'type' => EvidenceType::NOTE,
            'title' => 'Ghi chú: Terraform với Docker provider',
            'days_ago' => 40,
            'summary' => 'Network, volume và image được Terraform quản lý.',
            'is_public' => false,
            'skills' => ['terraform', 'docker'],
            'tags' => ['devops'],
            'external_key' => 'notes/terraform-docker-provider.md',
        ],
    ];

    /**
     * Goals matched by skill and note. An achieved goal's target is at or below the skill's level.
     *
     * @var list<array{skill: string, target_level: SkillLevelEnum, status: GoalStatus, target_in_days: int, achieved_days_ago?: int, note: string}>
     */
    private const GOALS = [
        ['skill' => 'postgresql', 'target_level' => SkillLevelEnum::CAN_TEACH, 'status' => GoalStatus::OPEN, 'target_in_days' => 180, 'note' => 'Viết loạt bài về index và query plan.'],
        ['skill' => 'docker', 'target_level' => SkillLevelEnum::INDEPENDENT, 'status' => GoalStatus::OPEN, 'target_in_days' => 60, 'note' => 'Tự dựng toàn bộ stack không cần tra cứu.'],
        ['skill' => 'laravel', 'target_level' => SkillLevelEnum::INDEPENDENT, 'status' => GoalStatus::ACHIEVED, 'target_in_days' => -150, 'achieved_days_ago' => 200, 'note' => 'Hoàn thành API đầu tiên một mình.'],
        ['skill' => 'terraform', 'target_level' => SkillLevelEnum::WITH_HELP, 'status' => GoalStatus::DROPPED, 'target_in_days' => 30, 'note' => 'Tạm dừng, chưa cần hạ tầng production.'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DevSampleSeeder never runs in production.');
        }

        $this->viewer();
        $this->endUser();
        $tagIds = $this->tags();
        $skillIds = $this->skills($tagIds);
        $this->evidence($skillIds, $tagIds);
        $this->goals($skillIds);

        $this->command->info('Sample data ready: viewer account `'.self::VIEWER_USER_NAME.'`, end user `'.self::END_USER_NAME.'`, '
            .count(self::SKILLS).' skills, '.count(self::EVIDENCE).' evidence, '.count(self::GOALS).' goals.');
    }

    private function endUser(): void
    {
        // The model cast hashes the password
        EndUser::firstOrCreate(
            ['username' => self::END_USER_NAME],
            ['display_name' => 'Demo visitor', 'password' => self::END_USER_PASSWORD, 'is_active' => true],
        );
    }

    private function viewer(): void
    {
        $viewer = AdminMst::firstOrCreate(
            ['user_name' => self::VIEWER_USER_NAME],
            [
                'email' => 'viewer@gmail.com',
                'password' => Hash::make(self::VIEWER_PASSWORD),
                'first_name' => 'Read',
                'last_name' => 'Only',
                'status' => 1,
                'is_active' => true,
            ]
        );
        if ($viewer->wasRecentlyCreated) {
            $viewer->assignRole(AdminRole::VIEWER->value);
        }
    }

    /**
     * @return array<string, int>
     */
    private function tags(): array
    {
        return collect(self::TAGS)
            ->mapWithKeys(fn (string $name): array => [$name => Tag::firstOrCreate(['name' => $name])->id])
            ->all();
    }

    /**
     * @param  array<string, int>  $tagIds
     * @return array<string, int>
     */
    private function skills(array $tagIds): array
    {
        $ids = [];
        foreach (self::SKILLS as $slug => $data) {
            $levels = $data['levels'];
            $skill = Skill::firstOrCreate(['slug' => $slug], [
                'name' => $data['name'],
                'category' => $data['category'],
                'description' => $data['description'],
                'is_public' => $data['is_public'],
                'current_level' => $levels[array_key_last($levels)][0],
            ]);

            // The history is written once, with the skill, so current_level always matches it
            if ($skill->wasRecentlyCreated) {
                foreach ($levels as [$level, $daysAgo, $reason]) {
                    SkillLevel::create([
                        'skill_id' => $skill->id,
                        'level' => $level,
                        'reason' => $reason,
                        'changed_on' => now()->subDays($daysAgo)->toDateString(),
                    ]);
                }
            }

            $skill->tags()->syncWithoutDetaching($this->pick($tagIds, $data['tags']));
            $ids[$slug] = $skill->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $skillIds
     * @param  array<string, int>  $tagIds
     */
    private function evidence(array $skillIds, array $tagIds): void
    {
        foreach (self::EVIDENCE as $url => $data) {
            $externalKey = $data['external_key'] ?? null;
            $evidence = Evidence::firstOrCreate(['url' => $url], [
                'type' => $data['type'],
                'title' => $data['title'],
                'occurred_on' => now()->subDays($data['days_ago'])->toDateString(),
                'summary' => $data['summary'],
                'is_public' => $data['is_public'],
                'source' => $externalKey === null ? EvidenceSource::MANUAL : EvidenceSource::OBSIDIAN,
                'external_key' => $externalKey,
            ]);
            $evidence->skills()->syncWithoutDetaching($this->pick($skillIds, $data['skills']));
            $evidence->tags()->syncWithoutDetaching($this->pick($tagIds, $data['tags']));
        }
    }

    /**
     * @param  array<string, int>  $skillIds
     */
    private function goals(array $skillIds): void
    {
        foreach (self::GOALS as $data) {
            $achievedDaysAgo = $data['achieved_days_ago'] ?? null;
            LearningGoal::firstOrCreate(
                ['skill_id' => $skillIds[$data['skill']], 'note' => $data['note']],
                [
                    'target_level' => $data['target_level'],
                    'target_date' => now()->addDays($data['target_in_days'])->toDateString(),
                    'status' => $data['status'],
                    'achieved_on' => $achievedDaysAgo === null ? null : now()->subDays($achievedDaysAgo)->toDateString(),
                ]
            );
        }
    }

    /**
     * @param  array<string, int>  $ids
     * @param  list<string>  $keys
     * @return list<int>
     */
    private function pick(array $ids, array $keys): array
    {
        return array_values(array_intersect_key($ids, array_flip($keys)));
    }
}
