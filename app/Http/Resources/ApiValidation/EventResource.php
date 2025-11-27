<?php

namespace App\Http\Resources\ApiValidation;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
                'ca' => $this->getTranslation('name', 'ca'), // Deprecated: removed in future app versions
                'default' => $this->name,
            ],
            'description' => [
                'ca' => $this->getTranslation('description', 'ca'), // Deprecated: removed in future app versions
                'default' => $this->description
            ],
            'slug' => [
                'ca' => $this->getTranslation('slug', 'ca'), // Deprecated: removed in future app versions
                'default' => $this->slug
            ],
            'image' => $this->image_url,
            'validate_all_event' => $this->validate_all_event,
            'sessions' => SessionResource::collection($this->whenLoaded('sessions_no_finished')),
        ];
    }
}
