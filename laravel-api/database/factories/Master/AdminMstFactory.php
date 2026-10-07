<?php

declare(strict_types=1);

namespace Database\Factories\Master;

use App\Enums\AdminRole;
use App\Enums\Gender;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminMst>
 */
class AdminMstFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AdminMst::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => Str::random(10).'@gmail.com',
            'user_name' => substr(fake()->unique()->userName(), 0, 15).Str::random(2),
            'password' => Hash::make('password'), // password
            'first_name' => substr(fake()->firstName(), 0, 15),
            'last_name' => substr(fake()->lastName(), 0, 15),
            'address' => substr(fake()->address(), 0, 50),
            'phone_number' => '0901234567',
            'birth' => fake()->dateTimeBetween('-50 years', '-18 years'),
            'gender' => fake()->randomElement([Gender::MALE->value, Gender::FEMALE->value]),
            'status' => 1,
            'is_active' => true,
            'role' => AdminRole::VIEWER,
            'avatar' => null,
            'email_verified_at' => now(),
            'is_delete' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * An admin who may change data (ADR-0005).
     */
    public function owner(): static
    {
        return $this->state(fn (): array => ['role' => AdminRole::OWNER]);
    }
}
