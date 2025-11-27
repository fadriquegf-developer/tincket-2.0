<?php

namespace App\Http\Resources\ApiValidation;

use Illuminate\Http\Resources\Json\JsonResource;

class InscriptionResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $isPack = $this->group_pack_id !== null;

        return [
            'id' => $this->id,
            'barcode' => $this->barcode,
            'cart_id' => $this->cart_id,
            'is_pack' => $isPack,
            'rate_name' => $isPack ? $this->group_pack->pack->name : $this->rate->name,
            'out_event' => (bool)$this->out_event,
            'checked_at' => $this->checked_at ? $this->checked_at->toISOString() : null,
            'updated_at' => $this->updated_at->toISOString()
        ];
    }
}
