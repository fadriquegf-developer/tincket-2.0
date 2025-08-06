<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Models\Region;
use App\Event;

class LocationApiController extends \App\Http\Controllers\Api\ApiController
{
    public function getRegionsAndCities()
    {
        $brand = request()->get('brand');

        // Obtén los IDs de las marcas asociadas
        $partners = $brand->partnershipedChildBrands->pluck('id')->toArray();
        $partners[] = $brand->id;

        // Optimizar la consulta utilizando joins en lugar de whereHas
        $regions = Region::select('regions.*')
            ->join('cities', 'cities.region_id', '=', 'regions.id')
            ->join('locations', 'locations.city_id', '=', 'cities.id')
            ->join('spaces', 'spaces.location_id', '=', 'locations.id')
            ->join('sessions', 'sessions.space_id', '=', 'spaces.id')
            ->whereIn('sessions.brand_id', $partners)
            ->where('sessions.ends_on', '>', now()) // Asegúrate de filtrar sesiones activas
            ->with(['cities' => function ($q) use ($partners) {
                $q->whereHas('location.spaces.next_sessions', function ($q) use ($partners) {
                    $q->whereIn('brand_id', $partners)
                        ->where('ends_on', '>', now());
                });
            }])
            ->distinct()
            ->get();

        return $this->json($regions);
    }
}
