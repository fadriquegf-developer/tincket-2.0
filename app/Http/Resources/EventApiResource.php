<?php

namespace App\Http\Resources;

use App\Http\Resources\SessionApiCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class EventApiResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'event_name' => ["ca" => $this->name],
            'event_slug' => ["ca" => $this->slug],
            'sessions' => new SessionApiCollection($this->sessions),
        ];
    }
}
