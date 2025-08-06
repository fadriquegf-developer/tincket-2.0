<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\PostResource;

class PostCollection extends ResourceCollection
{
    
    public function toArray($request)
    {
        return PostResource::collection($this->collection);
    }
}
