<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Page;

/**
 * Description of PageApiController
 *
 * @author miquel
 */
class PageApiController extends \App\Http\Controllers\Api\ApiController
{
    public function show($id)
    {
        return $this->json($this->allBuilder()->findOrFail($id));
    }
    
    private function allBuilder()
    {
        return Page::ownedByBrand();
    }
}
