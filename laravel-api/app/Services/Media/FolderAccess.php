<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Constants\MediaConst;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

/**
 * Which folders of the file manager the signed-in admin may use (ADR-0017). A folder given to
 * departments is locked: only members of one of its active departments may see or use it and
 * everything below it; a locked folder inside another locked folder needs both. Owners see
 * everything, and so do background jobs and commands, which run without an admin.
 */
final class FolderAccess
{
    public function __construct(private readonly MediaRepository $media) {}

    /**
     * Paths of the locked folders the signed-in admin may not use. Deleted folders count too when
     * $withDeleted, for files in the trash.
     *
     * @return list<string>
     */
    public function blockedFolders(bool $withDeleted = false): array
    {
        $admin = Auth::user();
        if (! $admin instanceof AdminMst || $admin->hasRole(AdminRole::OWNER->value)) {
            return [];
        }

        // Asked once per request: an evidence list checks every attached file
        $key = 'media.blocked_folders.'.(int) $withDeleted;
        $cache = request()->attributes;
        if (! $cache->has($key)) {
            $cache->set($key, $this->media->foldersLockedAgainst((int) $admin->id, $withDeleted));
        }

        /** @var list<string> */
        return $cache->get($key);
    }

    /** @param  list<string>  $blocked */
    public static function allows(string $path, array $blocked): bool
    {
        foreach ($blocked as $folder) {
            if ($path === $folder || str_starts_with($path, $folder.MediaConst::PATH_SEPARATOR)) {
                return false;
            }
        }

        return true;
    }

    /** @param  list<string>  $blocked */
    public static function holdsBlocked(string $folder, array $blocked): bool
    {
        foreach ($blocked as $path) {
            if (str_starts_with($path, $folder.MediaConst::PATH_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The row, or a 404 as if it did not exist when it lies in a folder the admin may not use.
     *
     * @throws ModelNotFoundException
     */
    public function ensureAllowed(MediaMgmt $item, bool $withDeleted = false): MediaMgmt
    {
        if (! self::allows($item->virtual_path, $this->blockedFolders($withDeleted))) {
            throw (new ModelNotFoundException)->setModel(MediaMgmt::class, [$item->id]);
        }

        return $item;
    }
}
