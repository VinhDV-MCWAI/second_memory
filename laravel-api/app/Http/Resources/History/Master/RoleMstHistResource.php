<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Master;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Master\RoleMstHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RoleMstHist
 */
class RoleMstHistResource extends JsonResource
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
            'role_mst_id' => (int) $this->role_mst_id,
            'name' => (string) $this->name,
            'permission' => (string) $this->permission,
            'is_active' => (bool) $this->is_active,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
