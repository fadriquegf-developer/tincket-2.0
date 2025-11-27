<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code_name' => $this->code_name,
            'logo' => $this->logo,
            'primary_color' => $this->primary_color,
            'description' => $this->description,
            'public_info' => $this->getPublicInfo(),
            // NO exponer:
            // - id
            // - allowed_host (información de infraestructura)
            // - capability_id
            // - parent_id
            // - extra_config (puede contener datos sensibles)
            // - settings (configuración interna)
        ];
    }

    /**
     * Obtiene solo la información pública de la marca
     */
    private function getPublicInfo()
    {
        return [
            'contact_email' => $this->getSetting('public.contact_email'),
            'website' => $this->getSetting('public.website'),
            'social_media' => $this->getSetting('public.social_media', []),
        ];
    }
}
