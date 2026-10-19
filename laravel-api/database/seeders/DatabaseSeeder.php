<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the dev database: permissions for every admin route (ADR-0013), the root owner and
     * sample data (DevSampleSeeder and DevContentSeeder refuse production).
     */
    public function run(): void
    {
        $this->command->call('permission:sync-routes');
        $this->call([
            RootAccountSeeder::class,
            DevSampleSeeder::class,
            DevContentSeeder::class,
        ]);
    }
}
