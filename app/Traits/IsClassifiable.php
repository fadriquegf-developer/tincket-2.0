<?php

namespace App\Traits;

use App\Models\Taxonomy;

/**
 *
 * @author jaumemk
 */
trait IsClassifiable
{

    public function taxonomies()
    {
        return $this->morphToMany(Taxonomy::class, 'classifiable')->active();
    }

    public function allTaxonomies()
    {
        return $this->morphToMany(Taxonomy::class, 'classifiable')->withoutGlobalScopes();;
    }
}
