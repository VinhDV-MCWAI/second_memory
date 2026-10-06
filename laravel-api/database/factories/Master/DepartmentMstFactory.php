<?php

namespace Database\Factories\Master;

use App\Enums\StatusEnum;
use App\Models\Master\DepartmentMst;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DepartmentMst>
 */
class DepartmentMstFactory extends Factory
{
    protected $model = DepartmentMst::class;

    public function definition()
    {
        return [
            'code' => Str::upper(Str::random(10)),
            'name' => fake()->company,
            'status' => StatusEnum::PUBLISHED->value,
            'is_delete' => 0,
        ];
    }
}
