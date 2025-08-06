<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\EventApiResource;

class EventApiCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     */
    public function toArray($request)
    {
        return EventApiResource::collection($this->collection);
    }
}
