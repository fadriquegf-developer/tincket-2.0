<?php

namespace App\Http\Resources;

use App\Http\Resources\SpaceResource;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionShowResource extends JsonResource
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
            "id" => $this->id,
            "user_id" => $this->user_id,
            "brand_id" => $this->brand_id,
            "event_id" => $this->event_id,
            "space_id" => $this->space_id,
            "space_configuration_id" => $this->space_configuration_id,
            "tpv_id" => $this->tpv_id,
            "is_numbered" => $this->is_numbered,
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
                "gl" => $this->getTranslation('description', 'gl'),
            ],
            'metadata' => [
                "ca" => $this->getTranslation('metadata', 'ca'),
                "es" => $this->getTranslation('metadata', 'es'),
                "en" => $this->getTranslation('metadata', 'en'),
                "gl" => $this->getTranslation('metadata', 'gl')
            ],
            'tags' => [
                "ca" => $this->getTranslation('tags', 'ca'),
                "es" => $this->getTranslation('tags', 'es'),
                "en" => $this->getTranslation('tags', 'en'),
                "gl" => $this->getTranslation('tags', 'gl')
            ],
            "max_places" => $this->max_places,
            "max_inscr_per_order" => $this->max_inscr_per_order,
            "starts_on" => $this->starts_on->format('Y-m-d H:i:s'),
            "ends_on" => $this->ends_on->format('Y-m-d H:i:s'),
            "inscription_starts_on" => $this->inscription_starts_on->format('Y-m-d H:i:s'),
            "inscription_ends_on" => $this->inscription_ends_on->format('Y-m-d H:i:s'),
            "created_at" =>  $this->created_at->format('Y-m-d H:i:s'),
            "updated_at" =>  $this->updated_at->format('Y-m-d H:i:s'),
            "images" => $this->images,
            "external_url" =>  $this->external_url,
            "autolock_type" => $this->autolock_type,
            "autolock_n" => $this->autolock_n,
            "limit_x_100" => $this->limit_x_100,
            "liquidation" => $this->liquidation,
            "visibility" => $this->visibility,
            "hide_n_positions" => $this->hide_n_positions,
            "count_free_positions" => $this->count_available_web_positions,
            "rates" => $this->all_rates_rates,
            "has_public_rates" => $this->has_public_rates,
            "redirect_to" => $this->redirect_to,
            "code_type" => $this->code_type,
            "only_pack" => $this->only_pack,
            'space' => new SpaceResource($this->space),
            'event' => new EventResource($this->event),
        ];
    }
}
