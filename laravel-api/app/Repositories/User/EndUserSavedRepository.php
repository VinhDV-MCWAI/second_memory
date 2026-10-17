<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Enums\SavedKind;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\User\EndUserSaved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * What an end user saved (ADR-0020). Only what the public site shows can be saved or listed:
 * a public skill, or a public, not hidden evidence of at least one public skill.
 */
class EndUserSavedRepository
{
    /**
     * @return Collection<int, EndUserSaved>
     */
    public function listFor(int $endUserId, ?SavedKind $kind): Collection
    {
        return EndUserSaved::query()
            ->where('end_user_id', $endUserId)
            ->when($kind, fn (Builder $query) => $query->where('kind', $kind))
            ->where(fn (Builder $query) => $query
                ->whereHas('skill', fn (Builder $skill) => $skill->where('is_public', true))
                ->orWhereHas('evidence', fn (Builder $evidence) => self::visibleEvidence($evidence)))
            ->with([
                'skill:id,name,slug',
                'evidence:id,title,type,public_key',
                'evidence.skills' => fn ($skills) => $skills->where('is_public', true)->orderBy('name')->select(['skill.id', 'name', 'slug']),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @throws ModelNotFoundException when the skill is missing or private
     */
    public function publicSkillId(string $slug): int
    {
        return (int) Skill::query()->where('is_public', true)->where('slug', $slug)->valueOrFail('id');
    }

    /**
     * @throws ModelNotFoundException when the evidence is missing or not on the public site
     */
    public function visibleEvidenceId(string $publicKey): int
    {
        return (int) self::visibleEvidence(Evidence::query())->where('public_key', $publicKey)->valueOrFail('id');
    }

    /**
     * @param  array{skill_id?: int, evidence_id?: int}  $target
     */
    public function save(int $endUserId, SavedKind $kind, array $target): void
    {
        EndUserSaved::query()->firstOrCreate(['end_user_id' => $endUserId, 'kind' => $kind, ...$target]);
    }

    /**
     * @param  array{skill_id?: int, evidence_id?: int}  $target
     */
    public function remove(int $endUserId, SavedKind $kind, array $target): void
    {
        EndUserSaved::query()->where(['end_user_id' => $endUserId, 'kind' => $kind, ...$target])->delete();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private static function visibleEvidence(Builder $query): Builder
    {
        return $query->where('is_public', true)
            ->whereNull('unpublished_at')
            ->whereHas('skills', fn (Builder $skill) => $skill->where('is_public', true));
    }
}
