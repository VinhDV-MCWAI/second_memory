<?php

declare(strict_types=1);

namespace App\Http\Resources\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\Management\EntryDescriptionMgmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EntryDescriptionMgmt
 */
class EntryDescriptionMgmtResource extends JsonResource
{
    use FormatsDates;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'summary' => (string) $this->summary,
            /** @var string The Tiptap document, JSON-encoded for the editor */
            'article' => json_encode($this->article, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => (int) $this->status?->value,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (int) $this->rank_order,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
