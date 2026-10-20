<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Enums\MoveDirection;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Rows ordered among siblings of one `parent_id`. The ↑ ↓ buttons (ADR-0022): a row trades places with its neighbour and the siblings are numbered
 * 1…n again, so nobody types order numbers. `sort_order` changes without touching `updated_at`:
 * reordering is not an edit of the content.
 */
trait MovesAmongSiblings
{
    /**
     * @param  Closure(Builder<Model>): mixed  $siblings  limits the query to the row's siblings
     */
    protected function swapWithNeighbour(Model $row, MoveDirection $direction, Closure $siblings): void
    {
        /** @var list<int> $ids */
        $ids = $this->model->newQuery()->where($siblings)->orderBy('sort_order')->orderBy('id')->pluck('id')->map(intval(...))->all();
        $index = array_search((int) $row->getKey(), $ids, true);
        $neighbour = $direction === MoveDirection::UP ? $index - 1 : $index + 1;
        if ($index === false || ! isset($ids[$neighbour])) {
            return;
        }

        [$ids[$index], $ids[$neighbour]] = [$ids[$neighbour], $ids[$index]];
        foreach ($ids as $position => $id) {
            $this->model->newQuery()->whereKey($id)->toBase()->update(['sort_order' => $position + 1]);
        }
    }

    /** Position of a row added under $parentId: after its last sibling */
    protected function nextPosition(?int $parentId): int
    {
        return (int) $this->model->newQuery()->where('parent_id', $parentId)->max('sort_order') + 1;
    }

    protected static function idOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
