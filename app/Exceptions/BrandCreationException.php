<?php

namespace App\Exceptions;

use Exception;

class BrandCreationException extends Exception
{
    /**
     * Los datos que causaron el error
     */
    protected $errorData = [];

    /**
     * Constructor
     */
    public function __construct($message = "", $code = 0, \Throwable $previous = null, array $errorData = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errorData = $errorData;
    }

    /**
     * Obtiene los datos del error
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Renderiza la excepción para respuestas HTTP
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => true,
                'data' => $this->errorData
            ], 422);
        }

        return back()
            ->withInput()
            ->withErrors(['brand' => $this->getMessage()]);
    }
}
