<?php

namespace App\Uploaders;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Uploaders\SingleFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;

/**
 * WebpImageUploader
 * --------------------------------------------------------------------------
 * • Acepta archivos normales y recortes base‑64 del field «image».
 * • Convierte la imagen a WebP (calidad 80).
 * • Reduce a anchura máxima opcional y genera conversiones opcionales.
 * • Borra el archivo anterior solo cuando el usuario pulsa “🗑 Clear”.
 *
 * Opciones extra en withFiles():
 *   'resize'      => ['max' => 1600],
 *   'conversions' => ['lg' => 1200, 'sm' => 600],
 */

class WebpImageUploader extends SingleFile
{
    protected ?int  $maxWidth    = null;
    protected array $conversions = [];

    public function __construct($crud, $field)
    {
        parent::__construct($crud, $field);

        $this->maxWidth    = data_get($field, 'resize.max');
        $this->conversions = data_get($field, 'conversions', []);
    }

    public function uploadFiles(Model $entry, $value = null)
    {
        $value = $value ?? CRUD::getRequest()->file($this->getName());

        if (!$value) {
            $raw = CRUD::getRequest()->input($this->getName());
            if (is_string($raw) && str_starts_with($raw, 'data:image')) {
                $value = $raw;
            }
        }

        $previousFile = $this->getPreviousFiles($entry);
        $inputRaw     = CRUD::getRequest()->input($this->getName());

        $wantsDelete = ($value === false) || ($inputRaw === null) || ($inputRaw === '') || ($inputRaw === 'null');

        if ($wantsDelete && $previousFile) {
            Storage::disk($this->getDisk())->delete($previousFile);
            return null;
        }

        if (
            ($value instanceof UploadedFile && $value->isValid()) ||
            (is_string($value) && str_starts_with($value, 'data:image'))
        ) {
            if ($previousFile) {
                Storage::disk($this->getDisk())->delete($previousFile);
            }

            $path     = $this->getPath();
            $fileName = 'img-' . Str::uuid() . '.webp';

            $img = $value instanceof UploadedFile
                ? Image::read($value->getRealPath())
                : Image::read($value);

            if ($this->maxWidth && $img->width() > $this->maxWidth) {
                $img->scale(width: $this->maxWidth);
            }

            // 1. Guardar imagen principal
            Storage::disk($this->getDisk())
                ->put($path . $fileName, $img->encode(new WebpEncoder(quality: 80)));

            // 2. Conversiones
            foreach ($this->conversions as $suffix => $w) {
                $variant = (clone $img)->scale(width: $w);
                Storage::disk($this->getDisk())
                    ->put($path . "{$suffix}-{$fileName}", $variant->encode(new WebpEncoder(quality: 80)));
            }

            return $path . $fileName;
        }

        return $previousFile;
    }
}
