<?php

namespace Database\Factories\Master;

use App\Models\Master\RoleMst;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleMstFactory extends Factory
{
    protected $model = RoleMst::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'permission' => '{}',
            'is_active' => true,
            'is_delete' => false,
        ];
    }
}
