<?php

namespace App\Observers;

use App\Models\Rate;

/**
 * Description of RateObserver
 *
 * @author miquel
 */
class RateObserver
{

    public function created(Rate $rate)
    {
        $rate->setTranslation('name', 'es', $rate->name);
        $rate->setTranslation('name', 'ca', $rate->name);
        $rate->setTranslation('name', 'gl', $rate->name);
        $rate->save();
    }

}
