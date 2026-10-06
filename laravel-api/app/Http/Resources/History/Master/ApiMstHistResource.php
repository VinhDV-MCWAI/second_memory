<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Master;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Master\ApiMstHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApiMstHist
 */
class ApiMstHistResource extends JsonResource
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
            'api_mst_id' => (int) $this->api_mst_id,
            'type' => (string) $this->type,
            'name' => (string) $this->name,
            'path' => (string) $this->path,
            'is_active' => (string) $this->is_active,
            'feature_mst_id' => (int) $this->feature_mst_id,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
