<?php

declare(strict_types=1);

use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 3: end-user management is removed (REQ-001).
 * The lab has no end users; only admins sign in.
 */
return new class extends Migration
{
    use ReplaysMigrations;

    private const TABLES = [
        'user_mgmt_hist',
        'user_mgmt',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        $this->replay([
            '0001_01_01_000026_create_user_mgmt_table.php',
            '0001_01_01_000045_create_user_mgmt_hist_table.php',
            '2026_10_05_000001_widen_address_on_user_mgmt_hist_table.php',
        ]);
    }
};
