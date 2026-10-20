<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use App\Models\User\EndUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Development only (refuses production): a read-only viewer account and an end user of the public
 * site. Sample categories and articles: DevContentSeeder.
 * Idempotent: rows are matched by a natural key and never duplicated or overwritten.
 */
class DevSampleSeeder extends Seeder
{
    public const VIEWER_USER_NAME = 'viewer';

    public const VIEWER_PASSWORD = '12345678';

    /** End user of the public site (ADR-0020), separate from the admin accounts */
    public const END_USER_NAME = 'demo';

    public const END_USER_PASSWORD = '12345678';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DevSampleSeeder never runs in production.');
        }

        $this->viewer();
        $this->endUser();

        $this->command->info('Sample accounts ready: viewer `'.self::VIEWER_USER_NAME.'`, end user `'.self::END_USER_NAME.'`.');
    }

    private function endUser(): void
    {
        // The model cast hashes the password
        EndUser::firstOrCreate(
            ['username' => self::END_USER_NAME],
            ['display_name' => 'Demo visitor', 'password' => self::END_USER_PASSWORD, 'is_active' => true],
        );
    }

    private function viewer(): void
    {
        $viewer = AdminMst::firstOrCreate(
            ['user_name' => self::VIEWER_USER_NAME],
            [
                'email' => 'viewer@gmail.com',
                'password' => Hash::make(self::VIEWER_PASSWORD),
                'first_name' => 'Read',
                'last_name' => 'Only',
                'status' => 1,
                'is_active' => true,
            ]
        );
        if ($viewer->wasRecentlyCreated) {
            $viewer->assignRole(AdminRole::VIEWER->value);
        }
    }
}
