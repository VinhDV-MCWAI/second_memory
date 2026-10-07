<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Master;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Master\FeatureMstHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FeatureMstHist
 */
class FeatureMstHistResource extends JsonResource
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
            'feature_mst_id' => (int) $this->feature_mst_id,
            'name' => (string) $this->name,
            'group_name' => (string) $this->group_name,
            'description' => (string) $this->description,
            'status' => (int) $this->status,
            'action' => (int) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
