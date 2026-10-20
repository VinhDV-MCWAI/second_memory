<?php

declare(strict_types=1);

namespace App\Http\Resources\Content;

use App\Http\Resources\Media\MediaResource;
use App\Models\Media\MediaMgmt;
use App\Services\Media\FolderAccess;
use Illuminate\Http\Request;

/**
 * A file of the file manager attached to content (ADR-0022): the file manager's view of it, plus
 * whether it can still be opened. A file deleted in the file manager stays attached (it may be
 * restored from the trash).
 *
 * @mixin MediaMgmt
 */
class AttachedFileResource extends MediaResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $file = parent::toArray($request);
        $available = $this->isAvailable();
        $locked = ! FolderAccess::allows((string) $this->virtual_path, app(FolderAccess::class)->blockedFolders());

        return [
            ...$file,
            /** How the file can be shown without downloading it; null: download only, not available, or locked */
            'preview' => $available && ! $locked ? $file['preview'] : null,
            'thumbnail_url' => $locked ? null : $file['thumbnail_url'],
            /** false: deleted in the file manager (in the trash, or gone for good) */
            'available' => $available,
            /** true: in a folder the signed-in admin may not use (ADR-0017); the name shows, the file does not open */
            'locked' => $locked,
        ];
    }
}
