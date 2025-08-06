<?php

namespace App\Http\Controllers\ApiBackend;

use Illuminate\Http\Request;

/**
 * This is a generic API to list or show an specific entity type list and 
 * entity type id
 *
 * @author miquel
 */
class EntityApiBackendController
{

    public function search(Request $request)
    {
        if (!$request->has('type'))
        {
            throw new \App\Exceptions\ApiException("Entity type must be indicated");
        }

        if ($request->has('id'))
            return $this->show($request);

        return $this->index($request);
    }

    private function index(Request $request)
    {
        return app()->make($request->get('type'))->ownedByBrand()->get();
    }

    private function show(Request $request)
    {
        return app()->make($request->get('type'))->ownedByBrand()->findOrFail($request->get('id'));
    }

}
