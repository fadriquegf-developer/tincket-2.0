<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionStaticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'starts_on' => $this->starts_on->format('Y-m-d H:i:s'),
            'name' => $this->name,
            'event' => $this->event,
            'brand' => [
                'name' => $this->brand->name,
                'code_name' => $this->brand->code_name,
            ]
        ];
    }
}
