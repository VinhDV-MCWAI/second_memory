<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Management;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\History\Management\UserMgmtHist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserMgmtHist
 */
class UserMgmtHistResource extends JsonResource
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
            'user_mgmt_id' => (int) $this->user_mgmt_id,
            'email' => (string) $this->email,
            'user_name' => (string) $this->user_name,
            'first_name' => (string) $this->first_name,
            'last_name' => (string) $this->last_name,
            'address' => (string) $this->address,
            'phone_number' => (string) $this->phone_number,
            'birth' => (string) $this->birth,
            'gender' => (int) $this->gender,
            'status' => (int) $this->status,
            'is_active' => (bool) $this->is_active,
            'avatar' => (string) $this->avatar,
            'action' => (int) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
