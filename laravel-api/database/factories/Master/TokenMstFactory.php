<?php

namespace Database\Factories\Master;

use App\Models\Master\TokenMst;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenMstFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TokenMst::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'token_hash' => md5($this->faker->uuid),
            'account_id' => 1, // Default, override in tests
            'device_name' => $this->faker->userAgent,
            'ip_address' => $this->faker->ipv4,
            'expired_at' => now()->addDays(7),
        ];
    }
}
