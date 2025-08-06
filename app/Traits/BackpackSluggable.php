<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Backpack\CRUD\app\Models\Traits\SpatieTranslatable\Sluggable as OriginalBackpackSluggable;

/**
 * Fix Syntax error data base not suport query with json. Override scopeFindSimilarSlugs and change query
 */
trait BackpackSluggable
{
    use OriginalBackpackSluggable;

     /**
     * Query scope for finding "similar" slugs, used to determine uniqueness.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $attribute
     * @param array $config
     * @param string $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindSimilarSlugs(Builder $query, string $attribute, array $config, string $slug): Builder
    {
        $separator = $config['separator'];
        $locale = $this->getLocale();
        
        return $query->where(function (Builder $q) use ($attribute, $slug, $separator, $locale) {
            $q->where($attribute, 'LIKE', '%"'.$locale.'":"'.$slug.'"%')
              ->orWhere($attribute, 'LIKE', '%"'.$locale.'":"'.$slug.$separator.'%"%');
        });

    }
}
