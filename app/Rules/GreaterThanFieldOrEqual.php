<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class GreaterThanFieldOrEqual implements Rule
{
    /**
     * Campo con el que se comparará.
     */
    public function __construct(private string $otherField) {}

    /**
     * Comprueba si $value ≥ valor del otro campo.
     */
    public function passes($attribute, $value): bool
    {
        $other = request($this->otherField);

        // Si el otro campo no viene en la request, deja pasar la validación
        // (ajusta esto si quieres un comportamiento distinto)
        if ($other === null) {
            return true;
        }

        return is_numeric($value) && $value >= $other;
    }

    /**
     * Mensaje de error (fallback, si no lo defines en lang/validation.php).
     */
    public function message(): string
    {
        return __('El valor máximo debe ser mayor o igual que el mínimo.');
    }
}
