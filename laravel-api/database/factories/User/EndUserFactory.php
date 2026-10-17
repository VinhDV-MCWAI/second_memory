<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\EndUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EndUser>
 */
class EndUserFactory extends Factory
{
    protected $model = EndUser::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'display_name' => fake()->name(),
            'password' => 'password',
            'is_active' => true,
        ];
    }

    public function locked(): static
    {
        return $this->state(['is_active' => false]);
    }
}
