<?php

namespace App\Exceptions;

use Exception;

class SlotNotAvailableException extends Exception
{
    /**
     * Código HTTP: 409 Conflict
     * 
     * Este código es apropiado porque indica que la solicitud no se puede
     * completar debido a un conflicto con el estado actual del recurso.
     */
    protected $code = 409;

    /**
     * Detalles de los conflictos encontrados
     * 
     * Estructura ejemplo:
     * [
     *     [
     *         'slot_id' => 123,
     *         'slot_name' => 'Fila A, Butaca 5',
     *         'session_id' => 456,
     *         'reason' => 'already_sold',  // o 'in_payment_by_another', 'blocked_by_another_cart'
     *         'existing_cart_id' => 789,
     *         'existing_confirmation_code' => '0000123-TK45'
     *     ],
     * ]
     */
    protected array $conflicts = [];

    /**
     * Constructor mejorado que acepta conflictos opcionales
     * 
     * @param string $message Mensaje de error
     * @param array $conflicts Detalles de los conflictos (opcional)
     * @param int $code Código HTTP (default: 409)
     * @param \Throwable|null $previous Excepción anterior
     */
    public function __construct(
        string $message = 'El asiento no está disponible',
        array $conflicts = [],
        int $code = 409,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->conflicts = $conflicts;
    }

    /**
     * Obtener los detalles de los conflictos
     * 
     * @return array
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    /**
     * ¿Hay conflictos detallados?
     * 
     * @return bool
     */
    public function hasConflicts(): bool
    {
        return !empty($this->conflicts);
    }

    /**
     * Obtener los IDs de los slots en conflicto
     * 
     * @return array
     */
    public function getConflictSlotIds(): array
    {
        return array_column($this->conflicts, 'slot_id');
    }

    /**
     * Renderizar la respuesta HTTP
     * 
     * Esta respuesta es capturada automáticamente por Laravel cuando
     * la excepción no es manejada en el controlador.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        $response = [
            'error' => 'slot_not_available',
            'message' => $this->getMessage(),
        ];

        // Incluir conflictos solo si existen
        if ($this->hasConflicts()) {
            $response['conflicts'] = $this->conflicts;
            $response['conflict_slot_ids'] = $this->getConflictSlotIds();
        }

        return response()->json($response, $this->code);
    }

    /**
     * Convertir a array (útil para logging)
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'conflicts' => $this->conflicts,
        ];
    }
}