<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Accounts made before ADR-0021 sign in with the part of their email before "@".
 */
final class EndUserUsernameMigrationTest extends TestCase
{
    public function test_existing_emails_become_free_usernames_and_down_restores_emails(): void
    {
        /** @var Migration $migration */
        $migration = require database_path('migrations/2026_10_19_100001_end_user_username.php');
        $migration->down();

        $password = Hash::make('password');
        foreach (['An.Nguyen@example.com', 'an.nguyen@other.org', 'x+tag@example.com'] as $email) {
            DB::table('end_user')->insert(['email' => $email, 'display_name' => 'Old', 'password' => $password, 'created_at' => now(), 'updated_at' => now()]);
        }

        $migration->up();
        $this->assertSame(['an.nguyen', 'an.nguyen-2', 'x-tag'], DB::table('end_user')->orderBy('id')->pluck('username')->all());
        $this->postJson('/api/user/login', ['username' => 'an.nguyen-2', 'password' => 'password'])->assertOk();

        $migration->down();
        $this->assertSame('x-tag@localhost', DB::table('end_user')->orderByDesc('id')->value('email'));
        $migration->up();
    }
}
