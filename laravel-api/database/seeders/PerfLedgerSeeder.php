<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EvidenceType;
use App\Enums\GoalStatus;
use App\Enums\SkillLevel;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Skill Ledger data at the REQ-002 volume for the k6 baseline (P3-16): 100 skills, 1,000
 * manual evidence links, 500 imported notes, 30 tags, 60 goals and one owner to log in with.
 * Runs only on the throwaway `perf` database (scripts/perf-baseline.sh), never on dev data.
 */
class PerfLedgerSeeder extends Seeder
{
    public const DATABASE = 'perf';

    public const OWNER_USER_NAME = 'perf_owner';

    public const OWNER_PASSWORD = 'perf-password';

    private const SKILLS = 100;

    private const MANUAL_EVIDENCE = 1000;

    private const IMPORTED_NOTES = 500;

    private const TAGS = 30;

    private const GOALS = 60;

    /** Mixed Vietnamese / English text, like the real ledger, so search has something to rank. */
    private const VI = ['kỹ năng', 'thiết kế', 'cơ sở dữ liệu', 'tối ưu', 'truy vấn', 'bảo mật', 'kiểm thử', 'triển khai', 'giám sát', 'hiệu năng', 'đồng bộ', 'phân quyền', 'sự cố', 'khôi phục', 'ghi chú', 'bộ nhớ đệm', 'hàng đợi', 'xử lý lỗi', 'tài liệu', 'kiến trúc'];

    private const EN = ['PostgreSQL', 'Laravel', 'Redis', 'Docker', 'Kubernetes', 'Terraform', 'Go', 'Python', 'React', 'Next.js', 'index', 'migration', 'OpenTelemetry', 'k6', 'Playwright', 'Sanctum', 'audit log', 'queue', 'cache', 'Nginx'];

    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== self::DATABASE) {
            throw new RuntimeException('PerfLedgerSeeder runs only on the `'.self::DATABASE.'` database.');
        }

        AdminMst::factory()->owner()->create([
            'user_name' => self::OWNER_USER_NAME,
            'password' => Hash::make(self::OWNER_PASSWORD),
        ]);

        $tagIds = collect(range(1, self::TAGS))
            ->map(fn (int $i): int => Tag::factory()->create(['name' => self::EN[$i % count(self::EN)].' '.$i])->id)
            ->all();

        $skills = collect(range(1, self::SKILLS))->map(fn (int $i): Skill => Skill::factory()
            ->atLevel(SkillLevel::from(1 + $i % 4))
            ->create([
                'name' => self::EN[$i % count(self::EN)].' '.self::VI[$i % count(self::VI)].' '.$i,
                'slug' => 'skill-'.$i,
                'description' => 'Mô tả '.self::VI[($i * 7) % count(self::VI)].' và '.self::EN[($i * 3) % count(self::EN)],
                'is_public' => $i % 3 !== 0,
            ]));
        $skillIds = $skills->pluck('id')->all();

        foreach ($skills as $skill) {
            $skill->tags()->attach(fake()->randomElements($tagIds, 2));
        }

        $this->evidence(self::MANUAL_EVIDENCE, $skillIds, $tagIds, imported: false);
        $this->evidence(self::IMPORTED_NOTES, $skillIds, $tagIds, imported: true);

        foreach (range(1, self::GOALS) as $i) {
            LearningGoal::factory()->create([
                'skill_id' => $skillIds[$i % count($skillIds)],
                'target_level' => SkillLevel::CAN_TEACH,
                'status' => $i % 5 === 0 ? GoalStatus::DROPPED : GoalStatus::OPEN,
                'note' => 'Mục tiêu '.self::VI[$i % count(self::VI)],
            ]);
        }
    }

    /**
     * @param  list<int>  $skillIds
     * @param  list<int>  $tagIds
     */
    private function evidence(int $count, array $skillIds, array $tagIds, bool $imported): void
    {
        foreach (range(1, $count) as $i) {
            $factory = $imported ? Evidence::factory()->fromObsidian() : Evidence::factory();
            $evidence = $factory->create([
                'type' => $imported ? EvidenceType::NOTE : fake()->randomElement([EvidenceType::PR, EvidenceType::ADR, EvidenceType::INCIDENT]),
                'title' => ucfirst(self::VI[($i * 7) % count(self::VI)]).' '.self::EN[($i * 5) % count(self::EN)].' #'.$i,
                'summary' => 'Ghi chú về '.self::VI[($i * 3) % count(self::VI)].' với '.self::EN[($i * 11) % count(self::EN)].'. '.str_repeat('Nội dung tóm tắt ngắn gọn. ', 1 + $i % 8),
                'occurred_on' => now()->subDays($i % 700)->toDateString(),
                'is_public' => $i % 4 !== 0,
            ]);
            $evidence->skills()->attach(fake()->randomElements($skillIds, 1 + $i % 3));
            $evidence->tags()->attach(fake()->randomElements($tagIds, $i % 3));
        }
    }
}
