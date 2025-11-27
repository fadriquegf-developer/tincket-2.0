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
        $pdfService = app(\App\Services\PdfGeneratorService::class);

        foreach ($urls as $url) {
            $brand = $this->brandFromUrl($url);

            // Obtener parámetros de configuración de la brand
            $params = brand_setting('base.inscription.ticket-web-params', []);

            // Asegurarse que NO está codificada
            $url = urldecode($url);

            if (!str_contains($url, 'brand_code=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . 'brand_code=' . urlencode($brand);
            }

            try {
                $pdf_content = $pdfService->generateFromUrl($url, $params);

                $tmp = sys_get_temp_dir() . '/' . Str::random(40) . '.pdf';
                File::put($tmp, $pdf_content);
                $tmpFiles->push($tmp);

                $merger->addPDF($tmp, 'all');
            } catch (\Exception $e) {
                Log::error('[PDFService] Error generando PDF', [
                    'url' => $url,
                    'brand' => $brand,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $out = sys_get_temp_dir() . '/' . Str::random(40) . '.pdf';
        $merger->merge();
        $merger->save($out);

        $tmpFiles->each(fn($f) => File::delete($f));

        return $out;
    }

    /** 
     * Convierte un set de inscripciones en un único PDF
     *  Usa PDFs guardados en lugar de regenerarlos
     */
    public function inscriptions(Collection $inscriptions, string $token): string
    {
        /** @var PDFMerger $merger */
        $merger = app(PDFMerger::class);
        $addedCount = 0;

        foreach ($inscriptions as $inscription) {
            $pdfPath = $this->getInscriptionPdfPath($inscription);

            if ($pdfPath && file_exists($pdfPath)) {
                try {
                    $merger->addPDF($pdfPath, 'all');
                    $addedCount++;
                } catch (\Exception $e) {
                    Log::error('[PDFService] Error al añadir PDF', [
                        'inscription_id' => $inscription->id,
                        'path' => $pdfPath,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                Log::warning('[PDFService] PDF no encontrado', [
                    'inscription_id' => $inscription->id,
                    'pdf_field' => $inscription->pdf,
                    'computed_path' => $pdfPath
                ]);
            }
        }

        // Si no se pudo añadir ningún PDF, intentar regenerar
        if ($addedCount === 0) {
            $urls = $inscriptions->map(fn($i) => route('open.inscription.pdf', [$i->id, 'token' => $token]));
            return $this->generate($urls);
        }

        $out = sys_get_temp_dir() . '/' . Str::random(40) . '.pdf';
        $merger->merge();
        $merger->save($out);

        return $out;
    }

    /**
     * Normaliza la ruta del PDF de una inscripción
     */
    private function getInscriptionPdfPath($inscription)
    {
        if (!$inscription->pdf) {
            return null;
        }

        $relativePath = $inscription->pdf;

        // Si ya tiene "app/" al principio, usarlo directamente
        if (str_starts_with($relativePath, 'app/')) {
            return storage_path($relativePath);
        }

        // Si no tiene prefijo, asumir que está en storage/app/
        return storage_path('app/' . ltrim($relativePath, '/'));
    }

    private function brandFromUrl(string $url): string
    {
        $noProto = substr($url, strpos($url, '//') + 2);
        return strstr($noProto, '.yesweticket.com', true);
    }
}
