<?php

declare(strict_types=1);

namespace Database\Factories\Master;

use App\Models\Master\PolicyDepartmentMst;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PolicyDepartmentMst>
 */
class PolicyDepartmentMstFactory extends Factory
{
    protected $model = PolicyDepartmentMst::class;

    public function definition()
    {
        return [
            'table_name' => 'table_'.Str::random(5),
            'row_id' => fake()->randomNumber(),
            'is_delete' => 0,
        ];
    }
}
