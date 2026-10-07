<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Management\EntryDescriptionMgmtHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EntryDescriptionMgmtHist
 */
class EntryDescriptionMgmtHistResource extends JsonResource
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
            'entry_description_mgmt_id' => (int) $this->entry_description_mgmt_id,
            'title' => (string) $this->title,
            'summary' => (string) $this->summary,
            'article' => (string) $this->article,
            'status' => (int) $this->status,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (int) $this->rank_order,
            'entry_id' => (int) $this->entry_id,
            'action' => (int) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
