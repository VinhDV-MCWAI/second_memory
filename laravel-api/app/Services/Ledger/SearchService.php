<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Constants\LedgerConst;
use App\Enums\SearchMatch;
use App\Enums\SkillLevel;
use App\Repositories\Ledger\SearchRepository;
use Illuminate\Support\Str;

/**
 * One search box over skills, goals and evidence (REQ-002 US-5, ADR-0009): full text first,
 * typo-tolerant trigram match only when full text finds nothing at all.
 */
final class SearchService
{
    public function __construct(private readonly SearchRepository $search) {}

    /**
     * @return array{match: SearchMatch, skills: list<array{id: int, title: string, snippet: string|null}>, goals: list<array{id: int, title: string, snippet: string|null}>, evidence: list<array{id: int, title: string, snippet: string|null}>}
     */
    public function search(string $query): array
    {
        // Letters and digits only: the words become a to_tsquery expression, so operators must not leak in
        $words = preg_split('/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return $this->format(SearchMatch::EXACT, ['skills' => [], 'goals' => [], 'evidence' => []]);
        }

        // The last word is a prefix, so results show up while typing ("ky nan" finds "kỹ năng")
        $results = $this->search->fullText(implode(' & ', $words).':*');
        if ($results['skills'] !== [] || $results['goals'] !== [] || $results['evidence'] !== []) {
            return $this->format(SearchMatch::EXACT, $results);
        }

        return $this->format(SearchMatch::FUZZY, $this->search->fuzzy(implode(' ', $words)));
    }

    /**
     * @param  array{skills: list<object>, goals: list<object>, evidence: list<object>}  $results
     * @return array{match: SearchMatch, skills: list<array{id: int, title: string, snippet: string|null}>, goals: list<array{id: int, title: string, snippet: string|null}>, evidence: list<array{id: int, title: string, snippet: string|null}>}
     */
    private function format(SearchMatch $match, array $results): array
    {
        $item = fn (object $row, ?string $title = null): array => [
            'id' => (int) $row->id,
            'title' => $title ?? (string) $row->title,
            'snippet' => $row->snippet === null ? null : Str::limit((string) $row->snippet, LedgerConst::SEARCH_SNIPPET_LENGTH),
        ];

        return [
            'match' => $match,
            'skills' => array_map(fn (object $row): array => $item($row), $results['skills']),
            // A goal reads as "PostgreSQL → Independent"
            'goals' => array_map(
                fn (object $row): array => $item($row, $row->title.' → '.SkillLevel::from((int) $row->target_level)->label()),
                $results['goals'],
            ),
            'evidence' => array_map(fn (object $row): array => $item($row), $results['evidence']),
        ];
    }
}
