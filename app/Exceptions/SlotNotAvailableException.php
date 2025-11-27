<?php

namespace App\Exceptions;

use Exception;

class SlotNotAvailableException extends Exception
{
    protected $code = 409; // Conflict

    public function render($request)
    {
        return response()->json([
            'error' => 'slot_not_available',
            'message' => $this->getMessage()
        ], $this->code);
    }
}
