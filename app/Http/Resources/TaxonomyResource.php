<?php

namespace App\Http\Resources;

use App\Http\Resources\PostCollection;
use App\Http\Resources\EventCollection;
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
            'active' => $this->when(isset($this->active), $this->active),
            'deleted_at' => $this->deleted_at,

            // ✅ AÑADIR: Children con recursividad
            'children' => TaxonomyResource::collection($this->whenLoaded('children')),

            // Relaciones opcionales (solo cuando están cargadas)
            'next_events' => $this->when(
                $this->relationLoaded('next_events') && $this->next_events !== null,
                new EventCollection($this->next_events)
            ),
            'published_posts' => $this->when(
                $this->relationLoaded('published_posts') && $this->published_posts !== null,
                new PostCollection($this->published_posts)
            ),
        ];
    }
}
