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

        if (!in_array($mailing->status, ['sent', 'processing']) && !blank(request()->mailing_content)) {

            $disk = Storage::disk('mailings');

            // Borrar archivos antiguos del mailing (sin subdirectorio)
            $files = $disk->files('');
            foreach ($files as $file) {
                if (str_starts_with(basename($file), "{$mailing->id}-")) {
                    $disk->delete($file);
                }
            }

            // Guardar SIN brandPath (el disco ya lo tiene)
            $htmlFilename = sprintf("%s.%s", $mailing->content_file_name, Mailing::HTML_EXTENSION);
            $plainFilename = sprintf("%s.%s", $mailing->content_file_name, Mailing::PLAIN_EXTENSION);

            $disk->put($htmlFilename, $mailing->content);
            $disk->put($plainFilename, html_entity_decode(strip_tags($mailing->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }

    public function retrieved(Mailing $mailing)
    {
        if ($mailing && $mailing->brand) {
            $disk = Storage::disk('mailings');
            $htmlFilename = $mailing->content_file_name . '.' . Mailing::HTML_EXTENSION;

            if ($disk->exists($htmlFilename)) {
                $mailing->setAttribute('content', $disk->get($htmlFilename));
            } else {
                \Log::warning("Archivo HTML del mailing no encontrado: $htmlFilename");
            }

            $mailing->syncOriginal();
        }
    }
}
