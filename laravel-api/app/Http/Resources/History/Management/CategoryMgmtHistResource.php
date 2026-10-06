<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Management\CategoryMgmtHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CategoryMgmtHist
 */
class CategoryMgmtHistResource extends JsonResource
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
            'category_mgmt_id' => (int) $this->category_mgmt_id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'description' => (string) $this->description,
            'status' => (string) $this->status,
            'is_display' => (bool) $this->is_display,
            'rank_order' => (string) $this->rank_order,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
