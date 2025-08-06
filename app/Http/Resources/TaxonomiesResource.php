<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class TaxonomiesResource extends JsonResource
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
            'name' => [
                "ca" => $this->getTranslation('name', 'ca'),
                "es" => $this->getTranslation('name', 'es'),
                "en" => $this->getTranslation('name', 'en'),
                "gl" => $this->getTranslation('name', 'gl')
            ],
            'slug' => [
                "ca" => $this->getTranslation('slug', 'ca'),
                "es" => $this->getTranslation('slug', 'es'),
                "en" => $this->getTranslation('slug', 'en'),
                "gl" => $this->getTranslation('slug', 'gl')
            ],
            'lft' => $this->lft,
            'rgt' => $this->rgt,
            'depth' => $this->depth,
            'parent_id' => $this->parent_id,
            'user_id' => $this->user_id,
            'brand_id' => $this->brand_id,
            'active' => $this->active,
            'children' => TaxonomiesResource::collection($this->whenLoaded('children')),  // Recursividad para hijos
        ];
    }
}
