<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\MediaConst;
use App\Enums\SearchMatch;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use App\Repositories\Content\SearchRepository;
use App\Services\Media\FolderAccess;
use App\Services\Media\MediaInfoService;
use Illuminate\Support\Facades\Auth;

/**
 * One search box over the names of files and folders (SEARCH-01): exact words first, the
 * typo-tolerant trigram match only when that finds nothing. Files are searched only for an admin
 * who may see Files, never in locked folders they may not use, never in the trash.
 *
 * @phpstan-type FileHit array{id: int, is_file: bool, name: string, path: string, size: int|null, thumbnail_url: string|null}
 */
final class SearchService
{
    public function __construct(
        private readonly SearchRepository $search,
        private readonly FolderAccess $access,
    ) {}

    /**
     * @return array{match: SearchMatch, files: list<FileHit>}
     */
    public function search(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $admin = Auth::user();
        $blocked = $admin instanceof AdminMst && $admin->can(MediaConst::LIST_PERMISSION) ? $this->access->blockedFolders() : null;
        if ($words === []) {
            return $this->format(SearchMatch::EXACT, []);
        }

        $files = $this->search->files($words, false, $blocked);
        if ($files !== []) {
            return $this->format(SearchMatch::EXACT, $files);
        }

        return $this->format(SearchMatch::FUZZY, $this->search->files($words, true, $blocked));
    }

    /**
     * @param  list<MediaMgmt>  $files
     * @return array{match: SearchMatch, files: list<FileHit>}
     */
    private function format(SearchMatch $match, array $files): array
    {
        return [
            'match' => $match,
            'files' => array_map(fn (MediaMgmt $file): array => [
                'id' => (int) $file->id,
                'is_file' => (bool) $file->is_file,
                'name' => (string) $file->original_name,
                'path' => (string) $file->virtual_path,
                'size' => $file->size,
                'thumbnail_url' => MediaInfoService::thumbnailUrl($file),
            ], $files),
        ];
    }
}
