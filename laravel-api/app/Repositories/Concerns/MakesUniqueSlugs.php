<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * "Thiết kế" → "thiet-ke"; a clash gets a numeric suffix ("thiet-ke-2").
 */
trait MakesUniqueSlugs
{
    /**
     * @param  Closure(Builder<Model>): mixed|null  $scope  limits where the slug must be free
     */
    protected function uniqueSlugFor(string $text, string $fallback, ?Closure $scope = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($text) ?: $fallback;
        $slug = $base;
        for ($i = 2; $this->slugTaken($slug, $scope, $ignoreId); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?Closure $scope, ?int $ignoreId): bool
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->when($scope, $scope)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
