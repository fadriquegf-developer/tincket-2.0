<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class MinImageWidth implements Rule
{
    protected int $minWidth;

    public function __construct(int $minWidth)
    {
        $this->minWidth = $minWidth;
    }

    public function passes($attribute, $value): bool
    {
        // 1) Nada que validar (no cambió la imagen)
        if ($value === null || $value === '') {
            return true;
        }

        // 2) Archivo subido normalmente (cuando crop=false o ciertos navegadores)
        if ($value instanceof UploadedFile) {
            $info = @getimagesize($value->getRealPath());
            if ($info === false) {
                return false;
            }
            $width = $info[0] ?? null;
            return is_int($width) && $width >= $this->minWidth;
        }

        // 3) Base64 desde el crop de Backpack (crop=true)
        if (is_string($value) && str_starts_with($value, 'data:image')) {
            // formato: data:image/png;base64,AAAA...
            $pos = strpos($value, ',');
            if ($pos === false) {
                return false;
            }
            $base64 = substr($value, $pos + 1);
            $binary = base64_decode($base64, true);
            if ($binary === false) {
                return false;
            }

            $info = @getimagesizefromstring($binary);
            if ($info === false) {
                return false;
            }
            $width = $info[0] ?? null;
            return is_int($width) && $width >= $this->minWidth;
        }

        // 4) Cualquier otro string (p. ej. 'uploads/.../poster-image.webp' o una URL)
        //     → interpretamos que es la imagen ya existente (no cambió), así que OK.
        if (is_string($value)) {
            return true;
        }

        // 5) Cualquier otro tipo inesperado => fallo
        return false;
    }

    public function message(): string
    {
        return "Las dimensiones de la imagen :attribute no son válidas. Ancho mínimo: {$this->minWidth}px.";
    }
}
