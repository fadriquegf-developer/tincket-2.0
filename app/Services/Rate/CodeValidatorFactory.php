<?php

namespace App\Services\Rate;

use App\Models\Rate;


class CodeValidatorFactory
{

    /**
     * Returns a validator related to a class
     * 
     * @param Rate $rate
     * @return CodeValidatorInterface
     * @throws \Exception
     */
    static public function getInstance(Rate $rate)
    {
        $class = sprintf("%s\\%sValidator", (new \ReflectionClass(static::class))->getNamespaceName(), $rate->validator_class);

        if (!class_exists($class))
            throw new \Exception("$class does not exists");

        return new $class($rate);
    }

}
