<?php

declare(strict_types=1);

namespace Database\Factories\Ledger;

use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\SkillLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillLevel>
 */
class SkillLevelFactory extends Factory
{
    protected $model = SkillLevel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => SkillLevelEnum::LEARNING,
            'reason' => fake()->sentence(),
            'changed_on' => now()->toDateString(),
            'admin_mst_id' => null,
        ];
    }
}
