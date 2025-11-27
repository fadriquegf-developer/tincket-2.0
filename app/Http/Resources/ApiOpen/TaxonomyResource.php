<?php

namespace App\Http\Resources\ApiOpen;

use Illuminate\Http\Resources\Json\JsonResource;


class TaxonomyResource extends JsonResource
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
            'name' => $this->getOriginal('name') ?? null,
            'slug' => $this->getOriginal('slug') ?? null,
            'lft' => $this->lft,
            'rgt' => $this->rgt,
            'depth' => $this->depth,
            'parent_id' => $this->parent_id,
            'children' => TaxonomyResource::collection($this->whenLoaded('children')),
        ];
    }
}
