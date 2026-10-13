<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the dev database: the root owner plus sample data (DevSampleSeeder refuses production).
     */
    public function run(): void
    {
        $this->call([
            RootAccountSeeder::class,
            DevSampleSeeder::class,
        ]);
    }
}
