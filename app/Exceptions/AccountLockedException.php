<?php

namespace App\Exceptions;

use Exception;

class AccountLockedException extends Exception
{
    protected $message = 'Account is temporarily locked due to multiple failed login attempts';
    protected $code = 423; // HTTP 423 Locked
}
