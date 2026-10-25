<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Rows of one table in a tree by `parent_id` (articles and their sub-pages, categories and the
 * categories inside them). The writes keep the tree acyclic, so every walk ends.
 */
trait WalksParentTree
{
    /**
     * Levels from the root down to this row, the root being 1.
     */
    public function depth(int $id): int
    {
        $table = $this->model->getTable();

        return (int) DB::selectOne(<<<SQL
            WITH RECURSIVE up(id, parent_id, depth) AS (
                SELECT id, parent_id, 1 FROM {$table} WHERE id = ?
                UNION ALL
                SELECT t.id, t.parent_id, up.depth + 1 FROM {$table} t JOIN up ON t.id = up.parent_id
            )
            SELECT max(depth) AS depth FROM up
            SQL, [$id])?->depth;
    }

    /**
     * Levels of the subtree under this row, the row itself being 1.
     */
    public function height(int $id): int
    {
        $table = $this->model->getTable();

        return (int) DB::selectOne(<<<SQL
            WITH RECURSIVE down(id, height) AS (
                SELECT id, 1 FROM {$table} WHERE id = ?
                UNION ALL
                SELECT t.id, down.height + 1 FROM {$table} t JOIN down ON t.parent_id = down.id
            )
            SELECT max(height) AS height FROM down
            SQL, [$id])?->height;
    }

    /**
     * Whether $candidate is $id itself or below it, at any level.
     */
    public function isSelfOrDescendant(int $candidate, int $id): bool
    {
        $table = $this->model->getTable();

        return DB::selectOne(<<<SQL
            WITH RECURSIVE down(id) AS (
                SELECT id FROM {$table} WHERE id = ?
                UNION ALL
                SELECT t.id FROM {$table} t JOIN down ON t.parent_id = down.id
            )
            SELECT EXISTS (SELECT 1 FROM down WHERE id = ?) AS found
            SQL, [$id, $candidate])?->found === true;
    }
}
