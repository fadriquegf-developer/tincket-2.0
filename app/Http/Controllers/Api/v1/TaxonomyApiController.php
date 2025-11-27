<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Taxonomy;
use App\Http\Resources\TaxonomyResource;
use Illuminate\Support\Facades\Cache;

class TaxonomyApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Display all taxonomies in a hirearchical tree
     *
     *
     */
    public function index()
    {
        $depth_subqueries = [
            'children',
            'children.children',
            'children.children.children',
            'children.children.children.children',
            'children.children.children.children.children'
        ];

        $taxonomies = $this->allBuilder()
            ->with(array_fill_keys($depth_subqueries, function ($query) {
                $query->orderBy('lft', 'asc');
            }))
            ->whereNull('parent_id')
            ->orderBy('lft', 'asc')
            ->get();


        return TaxonomyResource::collection($taxonomies);
    }

    public function show($id, $type = null)
    {

        // ✅ CAMBIO: Cargar múltiples niveles de children (igual que index)
        $depth_subqueries = [
            'children',
            'children.children',
            'children.children.children',
            'children.children.children.children',
        ];

        $taxonomy = \App\Models\Taxonomy::ownedByBrand()
            ->active()
            ->with(array_fill_keys($depth_subqueries, function ($query) {
                $query->orderBy('lft', 'asc');
            }))
            ->findOrFail($id);

        if (!is_null($type)) {
            $service = new \App\Services\Api\TaxonomyService($taxonomy);
            $taxonomy = $service->setRelation($type);
        }

        if ($type == 'posts') {
            return $this->json($taxonomy);
        }

        return new TaxonomyResource($taxonomy);
    }



    /**
     * The index and show method need to start from a common query. This method
     * provides this Query and every method applies afterward their own filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function allBuilder()
    {
        return Taxonomy::ownedByBrand()->active();
    }
}
