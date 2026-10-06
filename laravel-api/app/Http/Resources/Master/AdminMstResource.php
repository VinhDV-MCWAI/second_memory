<?php

namespace App\Http\Resources\Master;

use App\Constants\CommonVal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMstResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'email' => (string) $this->email,
            'user_name' => (string) $this->user_name,
            'password' => (string) $this->password,
            'first_name' => (string) $this->first_name,
            'last_name' => (string) $this->last_name,
            'address' => (string) $this->address,
            'phone_number' => (string) $this->phone_number,
            'birth' => $this->birth ? (string) $this->birth : null,
            'gender' => (int) $this->gender,
            'status' => (int) $this->status,
            'is_active' => (bool) $this->is_active,
            'avatar' => (string) $this->avatar,
            'is_delete' => (bool) $this->is_delete,
            'updated_at' => (string) date(CommonVal::DATE_FORMAT, strtotime($this->updated_at)),
        ];
    }
}
