<?php

namespace App\Services\Rate;

use App\Models\Rate;


abstract class CodeValidatorAbstract implements CodeValidatorInterface
{

    /** @var Rate */
    protected $rate;

    public function __construct(Rate $rate)
    {
        $this->rate = $rate;
    }

    public function getMessage()
    {
        return $this->message ?? null;
    }

}
