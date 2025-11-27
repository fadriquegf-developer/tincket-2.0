<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Para uso interno/admin - más información pero aún segura
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Solo mostrar información completa si el usuario es admin
        if (!$request->user() || !$request->user()->getIsSuperAdminAttribute()) {
            return (new PartnerResource($this->resource))->toArray($request);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code_name' => $this->code_name,
            'allowed_host' => $this->allowed_host,
            'capability' => [
                'id' => $this->capability_id,
                'name' => $this->capability?->name,
            ],
            'parent' => $this->when($this->parent_id, [
                'id' => $this->parent_id,
                'name' => $this->parent?->name,
            ]),
            'logo' => $this->logo,
            'primary_color' => $this->primary_color,
            'status' => $this->deleted_at ? 'inactive' : 'active',
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // Excluir configuraciones sensibles
            'settings_count' => $this->settings()->count(),
            'users_count' => $this->users()->count(),
        ];
    }
}
