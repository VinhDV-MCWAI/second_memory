<?php

namespace Database\Factories\Master;

use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'user_name' => substr($this->faker->unique()->userName(), 0, 15).Str::random(2),
            'password' => Hash::make('password'), // password
            'first_name' => substr($this->faker->firstName(), 0, 15),
            'last_name' => substr($this->faker->lastName(), 0, 15),
            'address' => substr($this->faker->address(), 0, 50),
            'phone_number' => '0901234567',
            'birth' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
            'gender' => $this->faker->randomElement([\App\Enums\Gender::MALE->value, \App\Enums\Gender::FEMALE->value]),
            'status' => 1,
            'is_active' => true,
            'avatar' => null,
            'email_verified_at' => now(),
            'is_delete' => false,
            'remember_token' => Str::random(10),
        ];
    }
}
