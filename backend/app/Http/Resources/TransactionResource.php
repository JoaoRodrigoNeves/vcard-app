<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $format = "full";

    public function toArray(Request $request): array
    {

        switch (TransactionResource::$format){
           case "mobile":
                return [
                    'id' => $this->id,
                    'vcard' => $this->vcard,
                    'value' => $this->value,
                    'old_balance' => $this->old_balance,
                    'new_balance' => $this->new_balance,
                    'created_at' => $this->created_at,
                    'payment_type' => $this->payment_type,
                ];
           default:
               return [
                   'id'=> $this->id,
                   'vcard' => $this->vcard,
                   'date' => $this->date,
                   'datetime'=> $this->datetime,
                   'type' => $this->type,
                   'value' => $this->value,
                   'old_balance' => $this->old_balance,
                   'new_balance' => $this->new_balance,
                   'payment_type' => $this->payment_type,
                   'payment_reference' => $this->payment_reference,
                   'pair_transaction' => $this->pair_transaction,
                   'pair_vcard' => $this->pair_vcard,
                   'category_id' => $this->category ? $this->category->id : null,
                   'category_name' => $this->category ? $this->category->name : null,
                   'description' => $this->description,
                   'custom_options' => $this->custom_options,
                   'custom_data' => $this->custom_data,
                   'created_at' => $this->created_at,
                   'updated_at' => $this->updated_at,
                   'deleted_at' => $this->deleted_at,
               ];
        }
    }
}
