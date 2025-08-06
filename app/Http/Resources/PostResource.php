<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class PostResource extends JsonResource
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
            'meta_description' => [
                "ca" => $this->getTranslation('meta_description', 'ca'),
                "es" => $this->getTranslation('meta_description', 'es'),
                "en" => $this->getTranslation('meta_description', 'en'),
                "gl" => $this->getTranslation('meta_description', 'gl'),
            ],
            'lead' => [
                "ca" => $this->getTranslation('lead', 'ca'),
                "es" => $this->getTranslation('lead', 'es'),
                "en" => $this->getTranslation('lead', 'en'),
                "gl" => $this->getTranslation('lead', 'gl')
            ],
            'body' => [
                "ca" => $this->getTranslation('body', 'ca'),
                "es" => $this->getTranslation('body', 'es'),
                "en" => $this->getTranslation('body', 'en'),
                "gl" => $this->getTranslation('body', 'gl')
            ],
            'publish_on' => (string) $this->publish_on,
            'image' => (string) $this->image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
