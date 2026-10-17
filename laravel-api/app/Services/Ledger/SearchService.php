<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Constants\LedgerConst;
use App\Constants\MediaConst;
use App\Enums\SearchMatch;
use App\Enums\SkillLevel;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use App\Repositories\Ledger\SearchRepository;
use App\Services\Media\FolderAccess;
use App\Services\Media\MediaInfoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * One search box over skills, goals, evidence (REQ-002 US-5, ADR-0009) and the names of files and
 * folders (SEARCH-01): full text first, typo-tolerant trigram match only when full text finds
 * nothing at all. Files are searched only for an admin who may see Files, never in locked folders
 * they may not use, never in the trash.
 */
final class SearchService
{
    public function __construct(
        private readonly SearchRepository $search,
        private readonly FolderAccess $access,
    ) {}

    /**
     * @return array{match: SearchMatch, skills: list<array{id: int, title: string, snippet: string|null}>, goals: list<array{id: int, title: string, snippet: string|null}>, evidence: list<array{id: int, title: string, snippet: string|null}>, files: list<array{id: int, is_file: bool, name: string, path: string, size: int|null, thumbnail_url: string|null}>}
     */
    public function search(string $query): array
    {
        // Letters and digits only: the words become a to_tsquery expression, so operators must not leak in
        $words = preg_split('/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return $this->format(SearchMatch::EXACT, ['skills' => [], 'goals' => [], 'evidence' => [], 'files' => []]);
        }
        $admin = Auth::user();
        $blocked = $admin instanceof AdminMst && $admin->can(MediaConst::LIST_PERMISSION) ? $this->access->blockedFolders() : null;

        // The last word is a prefix, so results show up while typing ("ky nan" finds "kỹ năng")
        $results = $this->search->fullText(implode(' & ', $words).':*', $words, $blocked);
        if (array_filter($results) !== []) {
            return $this->format(SearchMatch::EXACT, $results);
        }

        return $this->format(SearchMatch::FUZZY, $this->search->fuzzy($words, $blocked));
    }

    /**
     * @param  array{skills: list<object>, goals: list<object>, evidence: list<object>, files: list<MediaMgmt>}  $results
     * @return array{match: SearchMatch, skills: list<array{id: int, title: string, snippet: string|null}>, goals: list<array{id: int, title: string, snippet: string|null}>, evidence: list<array{id: int, title: string, snippet: string|null}>, files: list<array{id: int, is_file: bool, name: string, path: string, size: int|null, thumbnail_url: string|null}>}
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
            'files' => array_map(fn (MediaMgmt $file): array => [
                'id' => (int) $file->id,
                'is_file' => (bool) $file->is_file,
                'name' => (string) $file->original_name,
                'path' => (string) $file->virtual_path,
                'size' => $file->size,
                'thumbnail_url' => MediaInfoService::thumbnailUrl($file),
            ], $results['files']),
        ];
    }
}
