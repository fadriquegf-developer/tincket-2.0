<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
                "ca" => $this->name,
                "es" => $this->name,
                "en" => $this->name,
                "gl" => $this->name
            ],
            'slug' => [ 
                "ca" => $this->slug,
                "es" => $this->slug,
                "en" => $this->slug,
                "gl" => $this->slug
            ],
            'description' => [
                "ca" => $this->description,
                "es" => $this->description,
                "en" => $this->description,
                "gl" => $this->description
            ],
            "image" => $this->image,
            "address" => $this->address,
            "city" => $this->city->name,
            "postal_code" => $this->postal_code,
            "email" => $this->email,
            "phone1" => $this->phone1,
            "phone2" => $this->phone2,
            "other_info" => $this->other_info,
            "user_id" => $this->user_id,
            "brand_id" => $this->brand_id,
            "city_id" => $this->city_id,
        ];
    }
}
