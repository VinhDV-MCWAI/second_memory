<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\EvidenceType;
use App\Enums\GoalStatus;
use App\Enums\SkillLevel;
use PHPUnit\Framework\TestCase;

final class LedgerEnumsTest extends TestCase
{
    public function test_skill_level_is_the_four_step_scale_from_req_002(): void
    {
        $this->assertSame([1, 2, 3, 4], array_map(fn (SkillLevel $l): int => $l->value, SkillLevel::cases()));
        $this->assertSame('Learning', SkillLevel::LEARNING->label());
        $this->assertSame('Can use with help', SkillLevel::WITH_HELP->label());
        $this->assertSame('Independent', SkillLevel::INDEPENDENT->label());
        $this->assertSame('Can teach others', SkillLevel::CAN_TEACH->label());
    }

    public function test_evidence_types_and_goal_statuses(): void
    {
        $this->assertSame(['pr', 'adr', 'incident', 'note', 'other'], array_column(EvidenceType::cases(), 'value'));
        $this->assertSame(['open', 'achieved', 'dropped'], array_column(GoalStatus::cases(), 'value'));
    }
}
