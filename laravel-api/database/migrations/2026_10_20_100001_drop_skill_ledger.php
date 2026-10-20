<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * ADR-0022: the Skill Ledger (skills, levels, goals, evidence, tags) is removed; categories and
 * articles hold the owner's experience. The data was sample data (REQ-005), it is not moved.
 * End users keep only saved articles. The audit log keeps its old rows.
 */
return new class extends Migration
{
    use ReplaysMigrations;

    /** Child tables first */
    private const TABLES = ['evidence_media', 'evidence_tag', 'evidence_skill', 'skill_tag', 'skill_level', 'learning_goal', 'evidence', 'tag', 'skill'];

    private const PERMISSIONS = [
        'skill.list', 'skill.store', 'skill.update', 'skill.delete',
        'tag.list', 'tag.store', 'tag.update', 'tag.delete',
        'evidence.list', 'evidence.store', 'evidence.update', 'evidence.delete',
        'learning-goal.list', 'learning-goal.store', 'learning-goal.update', 'learning-goal.delete',
        'skill-level.list', 'skill-level.store',
        'evidence.import',
    ];

    private const VIEWER_READS = ['skill.list', 'tag.list', 'evidence.list', 'learning-goal.list', 'skill-level.list'];

    public function up(): void
    {
        DB::table('end_user_saved')->whereNull('article_id')->delete();
        DB::statement('ALTER TABLE end_user_saved DROP CONSTRAINT end_user_saved_one_target');
        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->dropConstrainedForeignId('skill_id');
            $table->dropConstrainedForeignId('evidence_id');
        });
        DB::statement('ALTER TABLE end_user_saved ALTER COLUMN article_id SET NOT NULL');

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Permission::query()->where('guard_name', CommonVal::ADMIN_GUARD)->whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $this->replay([
            '2026_10_08_100002_create_skill_ledger_tables.php',
            '2026_10_08_100003_add_ledger_search.php',
            '2026_10_08_100005_store_ledger_search_vectors.php',
            '2026_10_09_100002_weight_ledger_search_vectors.php',
            '2026_10_15_200001_create_evidence_media_table.php',
            '2026_10_15_200002_add_public_id_to_evidence_media_table.php',
            '2026_10_15_300001_make_evidence_url_nullable.php',
        ]);

        DB::statement('ALTER TABLE end_user_saved ALTER COLUMN article_id DROP NOT NULL');
        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->constrained('skill')->cascadeOnDelete();
            $table->foreignId('evidence_id')->nullable()->constrained('evidence')->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE end_user_saved ADD CONSTRAINT end_user_saved_one_target CHECK (num_nonnulls(skill_id, evidence_id, article_id) = 1)');
        DB::statement('CREATE UNIQUE INDEX end_user_saved_skill_unique ON end_user_saved (end_user_id, kind, skill_id) WHERE skill_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX end_user_saved_evidence_unique ON end_user_saved (end_user_id, kind, evidence_id) WHERE evidence_id IS NOT NULL');

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, CommonVal::ADMIN_GUARD);
        }
        Role::findByName(AdminRole::VIEWER->value, CommonVal::ADMIN_GUARD)->givePermissionTo(self::VIEWER_READS);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
