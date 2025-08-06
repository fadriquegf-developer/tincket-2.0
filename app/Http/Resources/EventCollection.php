<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\EventResource;

class EventCollection extends ResourceCollection
{
    
    public function toArray($request)
    {
        return EventResource::collection($this->collection);
    }
}
