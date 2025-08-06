<?php

namespace App\Exceptions;


class ApiException extends \Exception
{
    // all Tincket error will mask 90xxx
    // 901xx mask are Cart related errors
    const ERROR_CART_EXPIRED = 90101; // may it be defined in a Error enumeration file?
    const ERROR_CART_CONFIRMED = 90102;

    // 902xx mask are Slot related errors
    const SLOT_IS_UNAVAILABLE = 90201;

    protected $http_code;

    /**
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     * @param int $http_code optional http response code. 400 (Bad Request) by default
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, $http_code = 400)
    {
        parent::__construct($message, $code, $previous);
        $this->http_code = $http_code;
    }

    public function getHttpCode()
    {
        return $this->http_code;
    }

}
