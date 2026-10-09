<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Constants\LedgerConst;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Admin search over skills, goals and evidence (ADR-0009). Every text is compared through
 * f_unaccent(lower(...)), so "ky nang" finds "Kỹ năng". Skills and evidence keep that text in
 * `search_text` and its weighted vector in `search_tsv` (stored, GIN; title / name ranks first); `search_text` also has a GiST trigram
 * index for the typo fallback. Goals have no search columns: they are few, and they match on
 * their note and their skill's name.
 */
final class SearchRepository
{
    private const GOAL_TEXT = "f_unaccent(lower(coalesce(learning_goal.note, '') || ' ' || skill.name))";

    /**
     * Full-text match; `$tsQuery` is a to_tsquery string such as `ky & nang:*`.
     *
     * @return array{skills: list<object>, goals: list<object>, evidence: list<object>}
     */
    public function fullText(string $tsQuery): array
    {
        $match = function (string $text, ?string $vector): array {
            // Rank on the stored, weighted vector where there is one; recomputing it per match was the cost (DB-01)
            $vector ??= "to_tsvector('simple', {$text})";
            $weights = LedgerConst::SEARCH_RANK_WEIGHTS;

            return [
                "{$vector} @@ to_tsquery('simple', f_unaccent(lower(?)))",
                "ts_rank('{$weights}', {$vector}, to_tsquery('simple', f_unaccent(lower(?)))) DESC",
            ];
        };

        return $this->run($match, $tsQuery);
    }

    /**
     * Typo-tolerant match with pg_trgm word similarity, used only when full text finds nothing.
     *
     * @return array{skills: list<object>, goals: list<object>, evidence: list<object>}
     */
    public function fuzzy(string $text): array
    {
        return DB::transaction(function () use ($text): array {
            // Local to this transaction; the `<%` operator reads it. With the GiST index, ORDER BY `<<->`
            // walks the index in distance order and stops after SEARCH_LIMIT rows
            DB::select("SELECT set_config('pg_trgm.word_similarity_threshold', ?, true)", [LedgerConst::SEARCH_FUZZY_THRESHOLD]);
            $match = fn (string $column, ?string $vector): array => [
                "f_unaccent(lower(?)) <% {$column}",
                "f_unaccent(lower(?)) <<-> {$column}",
            ];

            return $this->run($match, $text);
        });
    }

    /**
     * @param  callable(string, ?string): array{0: string, 1: string}  $match  where clause and order by for a text expression and its stored vector, if any
     * @return array{skills: list<object>, goals: list<object>, evidence: list<object>}
     */
    private function run(callable $match, string $term): array
    {
        return [
            'skills' => $this->top(
                DB::table('skill')->select(['id', 'name as title', 'description as snippet']),
                $match('search_text', 'search_tsv'),
                $term,
            ),
            'goals' => $this->top(
                DB::table('learning_goal')
                    ->join('skill', 'skill.id', '=', 'learning_goal.skill_id')
                    ->select(['learning_goal.id', 'skill.name as title', 'learning_goal.target_level', 'learning_goal.note as snippet']),
                $match(self::GOAL_TEXT, null),
                $term,
            ),
            'evidence' => $this->top(
                DB::table('evidence')->select(['id', 'title', 'summary as snippet']),
                $match('search_text', 'search_tsv'),
                $term,
            ),
        ];
    }

    /**
     * @param  array{0: string, 1: string}  $match
     * @return list<object>
     */
    private function top(Builder $query, array $match, string $term): array
    {
        [$where, $orderBy] = $match;

        return $query->whereRaw($where, [$term])
            ->orderByRaw($orderBy, [$term])
            ->limit(LedgerConst::SEARCH_LIMIT)
            ->get()
            ->all();
    }
}
