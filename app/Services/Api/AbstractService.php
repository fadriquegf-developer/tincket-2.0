<?php

namespace App\Services\Api;

/**
 * Description of AbstractService
 *
 * @author miquel
 */
abstract class AbstractService
{

    /**
     * A Service may be used from Frontend side (API for example)
     * or backend side. Using them from backend side may produce
     * different behaviour. For example, an Inscription for a past
     * Session may be sold from Backend but not from Frontend. 
     * 
     * Setting the Service with enablePrivateUsage will allow it.
     * 
     * 
     */
    protected $private_usage = false;

    public function enablePrivateUsage()
    {
        $this->private_usage = true;

        return $this;
    }

    public function disablePrivateUsage()
    {
        $this->private_usage = false;

        return $this;
    }

}
