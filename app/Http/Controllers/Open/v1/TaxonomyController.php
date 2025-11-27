<?php

namespace App\Http\Controllers\Open\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiOpen\TaxonomyResource;

class TaxonomyController extends Controller
{

    /**
     * Display all taxonomies in a hirearchical tree
     *
     */
    public function index()
    {

        $max_depth = 5;
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
            ->where('parent_id', '=', NULL)
            ->orderBy('lft', 'asc')
            ->get();

        return TaxonomyResource::collection($taxonomies);
    }

    /**
     * The index and show method need to start from a common query. This method
     * provides this Query and every method applies afterward their own filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function allBuilder()
    {
        return \App\Models\Taxonomy::ownedByBrand()->active();
    }
}
