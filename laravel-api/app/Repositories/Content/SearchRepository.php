<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\SearchConst;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use Illuminate\Support\Facades\DB;

/**
 * Admin search over the names of files and folders (SEARCH-01). They come from the file manager's
 * repository, which knows the locked folders; `$blocked` null means the admin may not see Files.
 */
final class SearchRepository
{
    public function __construct(private readonly MediaRepository $media) {}

    /**
     * Names that contain every word; `$fuzzy` tolerates typos (pg_trgm, ADR-0009).
     *
     * @param  list<string>  $words
     * @param  list<string>|null  $blocked
     * @return list<MediaMgmt>
     */
    public function files(array $words, bool $fuzzy, ?array $blocked): array
    {
        if ($blocked === null) {
            return [];
        }
        if (! $fuzzy) {
            return $this->media->searchNames($words, false, $blocked, SearchConst::LIMIT)->all();
        }

        return DB::transaction(function () use ($words, $blocked): array {
            // Local to this transaction; the `<%` operator reads it
            DB::select("SELECT set_config('pg_trgm.word_similarity_threshold', ?, true)", [SearchConst::FUZZY_THRESHOLD]);

            return $this->media->searchNames($words, true, $blocked, SearchConst::LIMIT)->all();
        });
    }
}
