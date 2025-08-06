<?php

namespace App\Http\Resources;

use App\Http\Resources\LocationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
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
            "id" => $this->id,
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
            'description' => [
                "ca" => $this->getTranslation('description', 'ca'),
                "es" => $this->getTranslation('description', 'es'),
                "en" => $this->getTranslation('description', 'en'),
                "gl" => $this->getTranslation('description', 'gl')
            ],
            "capacity" => $this->capacity,
            "user_id" => $this->user_id,
            "brand_id" => $this->brand_id,
            "location_id" => $this->location_id,
            "created_at" =>  $this->created_at->format('Y-m-d H:i:s'),
            "updated_at" =>  $this->updated_at->format('Y-m-d H:i:s'),
            "deleted_at" => null,
            "svg_path" => $this->svg_path,
            "svg_host_path" => $this->svg_host_path,
            'location' => new LocationResource($this->location),
            'hide' => $this->hide,
            "zoom" => $this->zoom,
        ];
    }
}
