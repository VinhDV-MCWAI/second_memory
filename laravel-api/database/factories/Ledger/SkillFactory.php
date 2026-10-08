<?php

declare(strict_types=1);

namespace Database\Factories\Ledger;

use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\Skill;
use App\Models\Ledger\SkillLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Creates a private skill together with its first level entry, so current_level
 * always matches the history (RFC-002 §4.2).
 *
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' '.Str::random(4);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => fake()->randomElement(['backend', 'database', 'devops', 'frontend']),
            'description' => fake()->sentence(),
            'is_public' => false,
            'current_level' => SkillLevelEnum::LEARNING,
        ];
    }

    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }

    public function atLevel(SkillLevelEnum $level): static
    {
        return $this->state(['current_level' => $level]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Skill $skill): void {
            SkillLevel::factory()->create([
                'skill_id' => $skill->id,
                'level' => $skill->current_level,
            ]);
        });
    }
}
