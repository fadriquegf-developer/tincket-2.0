<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;

class ApiController extends BaseController
{

    /** @var array */
    private $fields;

    /**
     * Avoids to render the given fields in JSON object
     * 
     * @param mixed $field string or array of string
     * @return $this
     */
    protected function except($field)
    {
        if (is_string($field))
        {
            $field = array($field);
        }
        $this->fields = $field;

        return $this;
    }

    protected function json($response, $code = 200)
    {
        $this->applyFilters();

        return response()->json(['data' => $response, 'metadata' => $this->getMetadata()], $code)
                        ->header('X-Tincket-Brand-Id', request()->get('brand')->id ?? null)
        ;
    }

    /**
     * Apply the except filter to the data
     */
    private function applyFilters()
    {
        // The problem with array_forget is that is for named key arrays. When
        // we want to forget a nested array attribute (ie rates.pivot in 
        // SessionApiController) we need to do some hack.
        // 
        // This hack is still not working.
        
        /*
        foreach ($this->fields as $field)
        {
            $parts = explode('.', $field);

            while (count($parts) > 1)
            {
                $part = array_shift($parts);

                if (isset($this->data[$part]) && (is_array($this->data[$part]) || $this->data[$part] instanceof \Illuminate\Support\Collection))
                {
                    if ($this->data[$part] instanceof \Illuminate\Support\Collection)
                    {
                        $this->data[$part] = $this->data[$part]->toArray();
                    }
                    
                    dd($this->data[$part], $parts);
                    array_forget($this->data[$part], $parts);
                    dd($this->data[$part]);
                }
            }
            //array_forget($this->data, $field);
        }
        */
    }

    protected function getMetadata()
    {
        return [
            'cache' => [
                'force_clean' => \App\Services\Api\CacheClientService::needsToBeCleaned()
        ]];
    }

}
