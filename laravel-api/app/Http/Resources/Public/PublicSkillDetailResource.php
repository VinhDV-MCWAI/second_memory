<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Constants\LedgerConst;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Ledger\SkillLevel;
use App\Models\Media\MediaMgmt;
use App\Services\Media\MediaInfoService;
use Illuminate\Http\Request;

/**
 * One public skill with the dates of its level changes (no reasons, no recorder) and its
 * public evidence (REQ-002 US-3) with the files that can be downloaded (LEDGER-01: a random key
 * for `public/files/{key}`, no id, no folder path) and their thumbnails. Goals are never public.
 *
 * @mixin Skill
 */
class PublicSkillDetailResource extends PublicSkillResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'history' => $this->levels->map(fn (SkillLevel $entry): array => [
                'level' => $entry->level->value,
                'level_label' => $entry->level->label(),
                'changed_on' => $entry->changed_on->format(LedgerConst::DATE_FORMAT),
            ])->all(),
            'evidence' => $this->evidence->map(fn (Evidence $evidence): array => [
                'type' => $evidence->type,
                'title' => $evidence->title,
                /** @var string|null */
                'url' => $evidence->url,
                'occurred_on' => $evidence->occurred_on->format(LedgerConst::DATE_FORMAT),
                'summary' => $evidence->summary,
                /** @var list<array{key: string, name: string, size: int|null, mime_type: string|null, thumbnail_url: string|null}> */
                'files' => $evidence->availableFiles->map(fn (MediaMgmt $file): array => [
                    'key' => (string) $file->pivot?->getAttribute('public_id'),
                    'name' => (string) $file->original_name,
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    // Presigned for an hour (MEDIA-12): the page caches this answer 60 seconds
                    'thumbnail_url' => MediaInfoService::thumbnailUrl($file),
                ])->all(),
            ])->all(),
        ];
    }
}
