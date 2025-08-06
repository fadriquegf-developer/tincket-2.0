<?php

namespace App\Http\Resources;

use App\Http\Resources\SessionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            //'user_id' => $this->user_id,
            //'brand_id' => $this->brand_id,
            'name' => [ 
                "ca" => $this->getTranslation('name', 'ca'),
                "es" => $this->getTranslation('name', 'es'),
                "en" => $this->getTranslation('name', 'en'),
                "gl" => $this->getTranslation('name', 'gl')
            ],
            'lead' => [
                "ca" => $this->getTranslation('lead', 'ca'),
                "es" => $this->getTranslation('lead', 'es'),
                "en" => $this->getTranslation('lead', 'en'),
                "gl" => $this->getTranslation('lead', 'gl')
            ],
            'description' => [ 
                "ca" => $this->getTranslation('description', 'ca'),
                "es" => $this->getTranslation('description', 'es'),
                "en" => $this->getTranslation('description', 'en'),
                "gl" => $this->getTranslation('description', 'gl')
            ],
            'metadata' => [
                "ca" => $this->getTranslation('metadata', 'ca'),
                "es" => $this->getTranslation('metadata', 'es'),
                "en" => $this->getTranslation('metadata', 'en'),
                "gl" => $this->getTranslation('metadata', 'gl')
            ],
            'slug' => [
                "ca" => $this->getTranslation('slug', 'ca'),
                "es" => $this->getTranslation('slug', 'es'),
                "en" => $this->getTranslation('slug', 'en'),
                "gl" => $this->getTranslation('slug', 'gl')
            ],
            'tags' => [
                "ca" => $this->getTranslation('tags', 'ca'),
                "es" => $this->getTranslation('tags', 'es'),
                "en" => $this->getTranslation('tags', 'en'),
                "gl" => $this->getTranslation('tags', 'gl')
            ],
            'image' => $this->image,
            //'email' => $this->email,
            //'phone' => $this->phone,
            //'site' => $this->site,
            //'social' => $this->social,
            //'tags' => $this->tags,
            //'publish_on' => $this->publish_on,
            //'custom_logo' => $this->custom_logo,
            //'images' => $this->images,
            //'custom_text' => $this->custom_text,
            //'price_from' => $this->price_from,
            'rate_from' => $this->rate_from,
            'space' => $this->space,
            'show_calendar' => $this->show_calendar,
            'full_width_calendar' => $this->full_width_calendar,
            'hide_exhausted_sessions' => $this->hide_exhausted_sessions,
            'next_session' => (new SessionResource($this->next_session)),
            'first_session' => (new SessionResource($this->first_session)),
            'last_session' => (new SessionResource($this->last_session)),
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
