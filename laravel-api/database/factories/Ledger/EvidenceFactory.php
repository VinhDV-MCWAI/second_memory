<?php

declare(strict_types=1);

namespace Database\Factories\Ledger;

use App\Enums\EvidenceSource;
use App\Enums\EvidenceType;
use App\Models\Ledger\Evidence;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    protected $model = Evidence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => EvidenceType::PR,
            'title' => fake()->sentence(4),
            'url' => 'https://github.com/example/repo/pull/'.fake()->numberBetween(1, 9999),
            'occurred_on' => fake()->date(),
            'summary' => fake()->sentence(),
            'is_public' => false,
            'source' => EvidenceSource::MANUAL,
            'external_key' => null,
            'unpublished_at' => null,
        ];
    }

    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }

    public function fromObsidian(): static
    {
        return $this->state(fn (): array => [
            'type' => EvidenceType::NOTE,
            'source' => EvidenceSource::OBSIDIAN,
            'external_key' => 'notes/'.Str::slug(fake()->unique()->words(3, true)).'.md',
            'url' => 'https://notes.example.com/'.Str::random(8),
        ]);
    }
}
