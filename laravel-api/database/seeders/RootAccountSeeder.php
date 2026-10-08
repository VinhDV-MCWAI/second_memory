<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local development only: the first owner account (ADR-0005). Idempotent.
 */
class RootAccountSeeder extends Seeder
{
    public function run(): void
    {
        $admin = AdminMst::firstOrCreate(
            ['email' => 'root@gmail.com'],
            [
                'user_name' => 'root',
                'password' => Hash::make('12345678'),
                'first_name' => 'first',
                'last_name' => 'last',
                'status' => 1,
                'is_active' => true,
                'role' => AdminRole::OWNER,
            ]
        );

        $this->command->info("Root owner account created/found: {$admin->email}");
    }
}
