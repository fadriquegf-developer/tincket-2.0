<?php

namespace App\Observers;

use App\Models\Mailing;
use Illuminate\Support\Facades\Storage;


class MailingObserver
{

    public function saving(Mailing $mailing)
    {
        if (!isset(request()->mailing_content)) {
            // because 'content' is not in database we exclude it from update
            // then we will store in disk if it is dirty        
            $content = $mailing->content;

            request()->mailing_content = $content;

            unset($mailing->content);
        }
    }

    public function saved(Mailing $mailing)
    {
        $mailing->content = request()->mailing_content;

        if (blank($mailing->content)) {
            return;
        }

        if (!$mailing->is_sent && $mailing->isDirty(['content', 'slug'])) {

            $brandPath = $mailing->brand->code_name;
            $disk = Storage::disk('mailings');

            // Borrar archivos antiguos del mismo mailing ID dentro del directorio de la marca
            $files = $disk->files($brandPath);
            foreach ($files as $file) {
                if (str_starts_with(basename($file), "{$mailing->id}-")) {
                    $disk->delete($file);
                }
            }

            // Guardar nuevo contenido
            $htmlFilename = $brandPath . '/' . sprintf("%s.%s", $mailing->content_file_name, Mailing::HTML_EXTENSION);
            $plainFilename = $brandPath . '/' . sprintf("%s.%s", $mailing->content_file_name, Mailing::PLAIN_EXTENSION);

            $disk->put($htmlFilename, $mailing->content);
            $disk->put($plainFilename, html_entity_decode(strip_tags($mailing->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        }
    }

    public function retrieved(Mailing $mailing)
    {
        if ($mailing && $mailing->brand) {
            $path = storage_path('app/') . "mailing/{$mailing->brand->code_name}/{$mailing->content_file_name}." . Mailing::HTML_EXTENSION;

            if (file_exists($path)) {
                $mailing->setAttribute('content', file_get_contents($path));
            } else {
                \Log::warning("Archivo HTML del mailing no encontrado: $path");
            }

            $mailing->syncOriginal();
        }
    }

}
