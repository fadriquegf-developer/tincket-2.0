<?php

namespace App\Services;

class ElfinderAccessControl
{
    public function __invoke($attr, $path, $data, $volume, $isDir, $relpath)
    {
        $basename = basename($path);

        // Ocultar carpetas/archivos que empiecen con _ o .
        if (str_starts_with($basename, '_') || str_starts_with($basename, '.')) {
            return $attr == 'hidden' ? true : false;
        }

        return null;
    }
}
