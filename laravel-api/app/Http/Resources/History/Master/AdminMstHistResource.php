<?php

declare(strict_types=1);

namespace App\Http\Resources\History\Master;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMstHistResource extends JsonResource
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
            'admin_mst_id' => (int) $this->admin_mst_id,
            'email' => (string) $this->email,
            'user_name' => (string) $this->user_name,
            'password' => (string) $this->password,
            'first_name' => (string) $this->first_name,
            'last_name' => (string) $this->last_name,
            'address' => (string) $this->address,
            'phone_number' => (string) $this->phone_number,
            'birth' => (string) $this->birth,
            'gender' => (string) $this->gender,
            'status' => (string) $this->status,
            'is_active' => (bool) $this->is_active,
            'avatar' => (string) $this->avatar,
            'action' => (string) $this->action,
            'author_id' => (int) $this->author_id,
            'created_at' => $this->formatDate($this->created_at),
        ];
    }
}
