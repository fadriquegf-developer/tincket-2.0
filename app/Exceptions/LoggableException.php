<?php

namespace App\Exceptions;

/**
 * App will handle and monitor all this kind of exceptions
 *
 * @author miquel
 */
class LoggableException extends \RuntimeException
{
    const SECURITY = 10;       
    
    /**
     * 
     * @param string $message message to log
     * @param int $code internal error category defined inside class
     * @param int $http_code to return to user
     * @param \Throwable $previous
     */
    public function __construct(string $message = "", int $code = 0, int $http_code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }    
}
