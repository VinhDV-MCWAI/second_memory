<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Migrations\Migration;

/**
 * For migrations that drop legacy tables (RFC-001): `down()` re-runs the original
 * create migrations, so a rollback restores the structure (not the data; data
 * comes back only from a backup).
 */
trait ReplaysMigrations
{
    /**
     * @param  list<string>  $files  migration file names under database/migrations, in their original order
     */
    protected function replay(array $files): void
    {
        foreach ($files as $file) {
            /** @var Migration $migration */
            $migration = require database_path('migrations/'.$file);
            $migration->up();
        }
    }
}
