<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Validator;
use App\Models\Client;
use App\Models\FormField;

class FormApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Display a list of events with future sessions
     *
     */
    public function show($id)
    {

        // when we will support multiple forms we sould add
        // a field to the table called form_id.
        // This is why this method accepts the $id parameter.

        return $this->json(FormField::ownedByBrand()->whereNull('is_editable')->orderBy('weight', 'asc')->get());
    }

    public function store($id, Client $client, \App\Http\Requests\Api\FormApiRequest $request)
    {
        (new \App\Services\Api\ClientPreferencesService())->update($client, $request);

        return $this->json(null);
    }

    /**
     * Check if the user has all the required fields completed in the register form 
     * Return true if all ok
     * Return false if some rquired field is not present
     */
    public function registerCheckRequired(Client $client){
        $client->checkBrandOwnership();

        return $this->json($client->registerCheckRequired());
    }
}
