<?php

namespace App\Exceptions;

use Exception;

class SessionFullException extends Exception
{
    protected $code = 409;

    public function render($request)
    {
        return response()->json([
            'error' => 'session_full',
            'message' => $this->getMessage()
        ], $this->code);
    }
}
