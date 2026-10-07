<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Management;

use App\Models\History\Management\SettingLinkMgmtHist;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SettingLinkMgmtHist
 */
class SettingLinkMgmtHistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'setting_link_mgmt_id' => $this->setting_link_mgmt_id,
            'key' => $this->key,
            'value' => $this->value,
            'action' => $this->action,
            'author_id' => $this->author_id,
            'created_at' => $this->created_at,
        ];
    }
}
