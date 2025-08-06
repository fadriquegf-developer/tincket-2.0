<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Taxonomy;
use App\Http\Resources\TaxonomyResource;
use App\Http\Resources\TaxonomiesResource;

class TaxonomyApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Display all taxonomies in a hirearchical tree
     *
     *
     */
    public function index()
    {

        /* $max_depth = 5;
        $depth = 0;
        $depth_subqueries = [];

        // here we make a depth relations between taxonomies
        // ['children.children.children...'] => function($query){...}
        while ($depth < $max_depth) {
            $depth_subqueries[implode('.', array_fill(0, $depth + 1, 'children'))] = function ($query) {
                $query->orderBy('lft', 'asc');
            };
            $depth++;
        }

        $taxonomies = $this->allBuilder()
            ->with($depth_subqueries)
            ->whereNull('parent_id')
            ->orderBy('lft', 'asc')
            ->get(); */

        //Eliminamos ciclo while
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
        
        //return $this->json($taxonomies);
        // Retornar la colección de taxonomías transformada
        return TaxonomiesResource::collection($taxonomies);
    }

    public function show($id, $type = null)
    {
        $taxonomy = Taxonomy::ownedByBrand()->active()->findOrFail($id);

        // Si $type no es null, cargamos la relación
        if(!is_null($type)){
            $service = new \App\Services\Api\TaxonomyService($taxonomy);
            $taxonomy = $service->setRelation($type);
        }

        //Incluimos este if para Torello Mountain film, que requiere de posts
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
