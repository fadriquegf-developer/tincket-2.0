<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SessionStaticsCollection extends ResourceCollection
{
    
    public function toArray($request)
    {
        return SessionStaticsResource::collection($this->collection);
    }
}
