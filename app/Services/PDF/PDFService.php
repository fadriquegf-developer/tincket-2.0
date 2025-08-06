<?php

namespace App\Services\PDF;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webklex\PDFMerger\PDFMerger;

class PDFService
{
    /** Genera un PDF único a partir de una colección de URLs */
    public function generate(Collection $urls): string
    {
        /** @var PDFMerger $merger */
        $merger = app(PDFMerger::class);
        $tmpFiles = collect();

        foreach ($urls as $url) {

            $brand = $this->brandFromUrl($url);
            $params = brand_setting("brand.$brand.pdf.inscription.ticket-web-params", []);
            $render = env('TK_PDF_RENDERER', 'https://pdf.yesweticket.com');

            // Asegúrate que NO está codificada
            $url = urldecode($url);

            $paramString = $params ? '&' . http_build_query($params) : '';
            $fullUrl = "{$render}/render?url={$url}{$paramString}";

            $tmp = sys_get_temp_dir() . '/' . Str::random(40) . '.pdf';
            File::put($tmp, file_get_contents($fullUrl));
            $tmpFiles->push($tmp);

            $merger->addPDF($tmp, 'all');
        }

        $out = sys_get_temp_dir() . '/' . Str::random(40) . '.pdf';
        $merger->merge();
        $merger->save($out);

        $tmpFiles->each(fn($f) => File::delete($f));

        return $out;
    }

    /** Convierte un set de inscripciones en un único PDF */
    public function inscriptions(Collection $inscriptions, string $token): string
    {
        $urls = $inscriptions->map(fn($i) => route('open.inscription.pdf', [$i->id, 'token' => $token]));
        return $this->generate($urls);
    }

    private function brandFromUrl(string $url): string
    {
        $noProto = substr($url, strpos($url, '//') + 2);
        return strstr($noProto, '.yesweticket.com', true);
    }
}
