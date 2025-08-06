<?php

namespace App\Traits;

use Backpack\CRUD\app\Models\Traits\SpatieTranslatable\HasTranslations as BackpackHasTranslations;

trait HasTranslations
{
    use BackpackHasTranslations {
        getAttributeValue as protected backpackGetAttributeValue;
    }

    protected bool $translationsEnabled = true;

    /* -------- habilitar / deshabilitar -------- */

    public function enableTranslations(): static
    {
        $this->translationsEnabled = true;
        return $this;
    }

    public function disableTranslations(): static
    {
        $this->translationsEnabled = false;
        return $this;
    }

    /* -------- overrides compatibles con Eloquent -------- */

    #[\ReturnTypeWillChange]          // firma idéntica a la de Model
    public function getAttributeValue($key)
    {
        return $this->translationsEnabled
            ? $this->backpackGetAttributeValue($key)
            : parent::getAttributeValue($key);
    }

    #[\ReturnTypeWillChange]
    protected function asJson($value, $flags = 0)
    {
        // Fuerza a mantener tildes/ñ sin escapado
        $flags |= JSON_UNESCAPED_UNICODE;

        return parent::asJson($value, $flags);
    }
}