<?php

declare(strict_types=1);

use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 5: the content CMS and the public docs API are removed (REQ-001).
 * Content must be exported with `content:export-markdown` (v1.2.0) before this runs;
 * see docs/runbooks/content-export.md.
 */
return new class extends Migration
{
    use ReplaysMigrations;

    private const TABLES = [
        'category_mgmt_hist',
        'entry_mgmt_hist',
        'entry_description_mgmt_hist',
        'category_mgmt',
        'entry_mgmt',
        'entry_description_mgmt',
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
            '0001_01_01_000019_create_category_mgmt_table.php',
            '0001_01_01_000020_create_entry_mgmt_table.php',
            '0001_01_01_000022_create_entry_description_mgmt_table.php',
            '0001_01_01_000038_create_category_mgmt_hist_table.php',
            '0001_01_01_000039_create_entry_mgmt_hist_table.php',
            '0001_01_01_000040_create_entry_description_mgmt_hist_table.php',
            '2026_03_12_072201_add_layout_structure_to_category_mgmt_table.php',
            '2026_03_12_072202_add_layout_structure_to_category_mgmt_hist_table.php',
            '2026_03_12_072208_add_layout_structure_to_entry_mgmt_table.php',
            '2026_03_12_072209_add_layout_structure_to_entry_description_mgmt_table.php',
            '2026_03_12_072210_add_layout_structure_to_entry_mgmt_hist_table.php',
            '2026_03_12_072211_add_layout_structure_to_entry_description_mgmt_hist_table.php',
            '2026_04_01_000001_drop_layout_structure_from_entry_description_mgmt_table.php',
            '2026_04_01_000002_drop_layout_structure_from_entry_description_mgmt_hist_table.php',
        ]);
    }
};
