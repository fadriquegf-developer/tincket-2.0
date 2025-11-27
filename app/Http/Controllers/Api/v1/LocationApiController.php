<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Models\Region;
use App\Event;
use App\Scopes\BrandScope;

class LocationApiController extends \App\Http\Controllers\Api\ApiController
{
    public function getRegionsAndCities()
    {
        $brand = request()->get('brand');

        // Obtén los IDs de las marcas asociadas (principal + partners)
        $partners = $brand->partnershipedChildBrands->pluck('id')->toArray();
        $partners[] = $brand->id;

        $regions = Region::select('regions.*')
            ->join('cities', 'cities.region_id', '=', 'regions.id')
            ->join('locations', 'locations.city_id', '=', 'cities.id')
            ->join('spaces', 'spaces.location_id', '=', 'locations.id')
            ->join('sessions', 'sessions.space_id', '=', 'spaces.id')
            ->whereIn('sessions.brand_id', $partners)
            ->where('sessions.ends_on', '>', now())
            ->with(['cities' => function ($q) use ($partners) {
                $q->whereHas('location', function ($locQuery) {
                    $locQuery->withoutGlobalScope(BrandScope::class);
                })->whereHas('location.spaces', function ($spaceQuery) {
                    $spaceQuery->withoutGlobalScope(BrandScope::class);
                })->whereHas('location.spaces.sessions', function ($sessionQuery) use ($partners) {
                    $sessionQuery->withoutGlobalScope(BrandScope::class)
                        ->whereIn('brand_id', $partners)
                        ->where('ends_on', '>', now());
                });
            }])
            ->distinct()
            ->get();

        return $this->json($regions);
    }
}
