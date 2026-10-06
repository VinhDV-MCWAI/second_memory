<?php

namespace Database\Factories\Master;

use App\Models\Master\RoleMst;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoleMst>
 */
class RoleMstFactory extends Factory
{
    protected $model = RoleMst::class;

    public function definition()
    {
        return [
            // role_mst.name is unique varchar(30); a bare faker word repeats across rows.
            'name' => Str::limit(fake()->word(), 20, '').'-'.Str::lower(Str::random(6)),
            'permission' => '{}',
            'is_active' => true,
            'is_delete' => false,
        ];
    }
}
