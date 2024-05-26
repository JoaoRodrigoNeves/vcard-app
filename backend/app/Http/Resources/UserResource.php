<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_type' => $this->user_type,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'blocked' => $this->blocked,
            'created_at' => $this->created_at,
            'photo_url' => $this->photo_url,
        ];
    }
}
