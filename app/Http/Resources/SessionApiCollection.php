<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\SessionApiResource;

class SessionApiCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     */
    public function toArray($request)
    {
        return SessionApiResource::collection($this->collection);
    }
}
