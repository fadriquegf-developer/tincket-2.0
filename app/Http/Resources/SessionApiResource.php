<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionApiResource extends JsonResource
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
            'session_name' => ["ca" => $this->name],
            'session_slug' => ["ca" => $this->slug],
            'session_starts_on' => $this->starts_on->format('d-m-Y H:i'),
            'session_ends_on' => $this->ends_on->format('d-m-Y H:i'),
            'aforament_maxim' => $this->max_places,
            'num_entrades_venudes' => $this->inscriptions->where('barcode','!=',null)->count(),
            'percentatge_entrades_venudes' => round( ( $this->inscriptions->where('barcode','!=',null)->count()*100 ) / $this->max_places , 2 ),
            'num_asistencia_session' => $this->inscriptions->where('checked_at', '!=', null)->count(),
            'percetatge_asistencia_session' => round( ( $this->inscriptions->where('checked_at','!=',null)->count()*100 ) / $this->max_places , 2 ),
        ];
    }
}
