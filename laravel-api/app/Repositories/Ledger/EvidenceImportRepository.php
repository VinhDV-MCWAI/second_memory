<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Enums\EvidenceSource;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Queries of the Obsidian import (RFC-002 §4.5). Names are matched ignoring case, like the unique indexes.
 */
final class EvidenceImportRepository
{
    /**
     * @param  list<string>  $names  lower-case
     * @return Collection<int, Skill>
     */
    public function skillsByName(array $names): Collection
    {
        return $names === [] ? new Collection : Skill::query()->select(['id', 'name'])->whereIn(DB::raw('lower(name)'), $names)->get();
    }

    /**
     * @param  list<string>  $names  lower-case
     * @return Collection<int, Tag>
     */
    public function tagsByName(array $names): Collection
    {
        return $names === [] ? new Collection : Tag::query()->select(['id', 'name'])->whereIn(DB::raw('lower(name)'), $names)->get();
    }

    public function createTag(string $name): Tag
    {
        return Tag::query()->create(['name' => $name]);
    }

    /**
     * Every row the importer owns, hidden ones included, keyed by vault path.
     *
     * @return Collection<string, Evidence>
     */
    public function importedRows(): Collection
    {
        return Evidence::query()
            ->with(['skills:id,name', 'tags:id,name'])
            ->where('source', EvidenceSource::OBSIDIAN)
            ->get()
            ->keyBy('external_key');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $skillIds
     * @param  list<int>  $tagIds
     */
    public function create(array $attributes, array $skillIds, array $tagIds): Evidence
    {
        $evidence = Evidence::query()->create($attributes);
        $evidence->skills()->sync($skillIds);
        $evidence->tags()->sync($tagIds);

        return $evidence;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $skillIds
     * @param  list<int>  $tagIds
     */
    public function update(Evidence $evidence, array $attributes, array $skillIds, array $tagIds): void
    {
        $evidence->update($attributes);
        $evidence->skills()->sync($skillIds);
        $evidence->tags()->sync($tagIds);
    }

    public function hide(Evidence $evidence, Carbon $at): void
    {
        $evidence->update(['is_public' => false, 'unpublished_at' => $at]);
    }
}
