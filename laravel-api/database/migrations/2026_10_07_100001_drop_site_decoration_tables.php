<?php

declare(strict_types=1);

use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 2: sliders, banners, setting links and socials are removed (REQ-001).
 * Data is not migrated: these modules only decorated the old public site.
 */
return new class extends Migration
{
    use ReplaysMigrations;

    private const TABLES = [
        'slider_mgmt_hist',
        'banner_mgmt_hist',
        'social_mgmt_hist',
        'setting_link_mgmt_hist',
        'slider_mgmt',
        'banner_mgmt',
        'setting_link_mgmt',
        'social_mgmt',
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
            '0001_01_01_000023_create_slider_mgmt_table.php',
            '0001_01_01_000024_create_banner_mgmt_table.php',
            '0001_01_01_000025_create_setting_link_mgmt_table.php',
            '0001_01_01_000027_create_social_mgmt_table.php',
            '0001_01_01_000041_create_slider_mgmt_hist_table.php',
            '0001_01_01_000042_create_banner_mgmt_hist_table.php',
            '0001_01_01_000043_create_social_mgmt_hist_table.php',
            '0001_01_01_000044_create_setting_link_mgmt_hist_table.php',
            '2026_01_15_172000_add_media_id_to_banner_mgmt_table.php',
        ]);
    }
};
