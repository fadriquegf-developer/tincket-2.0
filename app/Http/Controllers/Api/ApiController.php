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
        if (is_string($field)) {
            $field = array($field);
        }
        $this->fields = $field;
        return $this;
    }

    protected function json($response, $code = 200)
    {
        return response()->json(
            ['data' => $response, 'metadata' => $this->getMetadata()],
            $code,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-Tincket-Brand-Id' => request()->get('brand')->id ?? null
            ],
            JSON_UNESCAPED_UNICODE
        );
    }

    protected function getMetadata()
    {
        // Simplificado sin el CacheClientService
        return [
            'cache' => [
                'force_clean' => false
            ]
        ];
    }
}
