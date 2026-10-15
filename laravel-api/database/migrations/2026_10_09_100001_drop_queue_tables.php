<?php

declare(strict_types=1);

use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * API-05: nothing queues jobs since ADR-0007 (config/queue.php keeps only the sync driver).
 */
return new class extends Migration
{
    use ReplaysMigrations;

    private const TABLES = ['jobs', 'job_batches', 'failed_jobs'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        $this->replay(['0001_01_01_000002_create_jobs_table.php']);
    }
};
