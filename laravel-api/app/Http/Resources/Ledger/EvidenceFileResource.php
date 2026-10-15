<?php

declare(strict_types=1);

namespace App\Http\Resources\Ledger;

use App\Http\Resources\Media\MediaResource;
use App\Models\Media\MediaMgmt;
use Illuminate\Http\Request;

/**
 * A file attached to an evidence (LEDGER-01): the file manager's view of it, plus whether it can
 * still be opened. A file deleted in the file manager stays attached (it may be restored from the trash).
 *
 * @mixin MediaMgmt
 */
class EvidenceFileResource extends MediaResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $file = parent::toArray($request);
        $available = $this->isAvailable();

        return [
            ...$file,
            /** How the file can be shown without downloading it; null: download only, or not available */
            'preview' => $available ? $file['preview'] : null,
            /** false: deleted in the file manager (in the trash, or gone for good) */
            'available' => $available,
        ];
    }
}
