<?php

namespace Database\Factories\Master;

use App\Models\Master\TokenMst;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TokenMst>
 */
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
            'token_hash' => md5(fake()->uuid),
            'account_id' => 1, // Default, override in tests
            'device_name' => fake()->userAgent,
            'ip_address' => fake()->ipv4,
            'expired_at' => now()->addDays(7),
        ];
    }
}
