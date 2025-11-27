<?php

namespace App\Http\Resources\ApiValidation;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
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
            'slug' => [
                'ca' => $this->getTranslation('slug', 'ca'), // Deprecated: removed in future app versions
                'default' => $this->slug,
            ],
            'starts_on' => $this->starts_on->toISOString(),
            'ends_on' => $this->ends_on->toISOString(),
            'space' => [
                'name' => [
                    'ca' => $this->space->getTranslation('name', 'ca'), // Deprecated: removed in future app versions
                    'default' => $this->space->name,
                ],
                'location' => [
                    'name' => [
                        'ca' => $this->space->location->getTranslation('name', 'ca'), // Deprecated: removed in future app versions
                        'default' => $this->space->location->name,
                    ]
                ],
                'hide' => $this->space->hide
            ],
            'validate_all_session' => $this->validate_all_session,
            'total' => $this->relationLoaded('inscriptions') ? $this->inscriptions->count() : 0,
            'n_validated' => $this->relationLoaded('inscriptions') ? $this->inscriptions->where('checked_at', true)->count() : 0,
            'n_out' => $this->relationLoaded('inscriptions') ? $this->inscriptions->where('out_event', true)->count() : 0,
        ];
    }
}
