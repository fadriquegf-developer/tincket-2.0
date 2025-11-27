<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * SOLO exponer datos públicos y seguros
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar ?? $this->getAvatarAttribute(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            // NUNCA incluir:
            // - id (puede ser usado para enumeration attacks)
            // - password
            // - api_token
            // - remember_token
            // - allowed_ips
            // - roles/permissions (información de seguridad)
        ];
    }
}
