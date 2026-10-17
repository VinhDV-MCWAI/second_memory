<?php

declare(strict_types=1);

namespace Database\Factories\Master;

use App\Models\Master\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'is_active' => true,
        ];
    }
}
