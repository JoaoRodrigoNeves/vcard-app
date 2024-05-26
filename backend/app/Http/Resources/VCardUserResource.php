<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VCardUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $format = "full";

    public function toArray(Request $request): array
    {
        return [
            'phone_number' => $this->phone_number,
            'name' => $this->name,
            'email' => $this->email,
            'photo_url' => $this->photo_url,
            'balance' => $this->balance,
            'max_debit' => $this->max_debit,
            'created_at' => $this->created_at,
            'isBlocked' => $this->blocked,
        ];
    }
}
