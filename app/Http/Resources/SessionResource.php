<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'space' => [
                'name' => [
                    "ca" => $this->space->getTranslation('name', 'ca'),
                    "es" => $this->space->getTranslation('name', 'es'),
                    "en" => $this->space->getTranslation('name', 'en'),
                    "gl" => $this->space->getTranslation('name', 'gl')
                ],
                'location' => [
                    'name' => [
                        "ca" => $this->space->location->getTranslation('name', 'ca'),
                        "es" => $this->space->location->getTranslation('name', 'es'),
                        "en" => $this->space->location->getTranslation('name', 'en'),
                        "gl" => $this->space->location->getTranslation('name', 'gl')
                    ]
                ],
                'hide' => $this->space->hide
            ],
            'starts_on' => $this->starts_on->format('Y-m-d H:i:s'),
            'ends_on' => $this->ends_on->format('Y-m-d H:i:s'),
            'inscription_ends_on' => $this->inscription_ends_on->format('Y-m-d H:i:s'),
            'only_pack' => $this->only_pack
        ];
    }
}