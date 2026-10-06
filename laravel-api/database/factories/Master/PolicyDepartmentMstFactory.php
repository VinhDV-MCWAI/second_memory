<?php

namespace Database\Factories\Master;

use App\Models\Master\PolicyDepartmentMst;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PolicyDepartmentMstFactory extends Factory
{
    protected $model = PolicyDepartmentMst::class;

    public function definition()
    {
        return [
            'table_name' => 'table_'.Str::random(5),
            'row_id' => $this->faker->randomNumber(),
            'is_delete' => 0,
        ];
    }
}
