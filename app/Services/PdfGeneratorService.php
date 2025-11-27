<?php

namespace App\Services;

use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class PdfGeneratorService
{
    /**
     * Genera un PDF desde una URL interna
     * 
     * @param string $url URL completa a renderizar
     * @param array $options Opciones de wkhtmltopdf
     * @return string Contenido del PDF en binario
     */
    public function generateFromUrl(string $url, array $options = []): string
    {
        $pdf = PDF::loadFile($url);

        // Aplicar opciones por defecto
        $this->applyOptions($pdf, $options);

        return $pdf->output();
    }

    /**
     * Aplica las opciones de formato al PDF
     */
    protected function applyOptions($pdf, array $options): void
    {
        // Papel y orientación
        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper']);
        }

        if (isset($options['orientation'])) {
            $pdf->setOrientation($options['orientation']);
        }

        // Dimensiones personalizadas
        if (isset($options['ph'])) {
            $pdf->setOption('page-height', $options['ph']);
        }

        if (isset($options['pw'])) {
            $pdf->setOption('page-width', $options['pw']);
        }

        // Márgenes
        $margin = $options['margin'] ?? null;
        if ($margin !== null) {
            $options['mt'] = $options['mr'] = $options['ml'] = $options['mb'] = $margin;
        }

        if (isset($options['mt'])) {
            $pdf->setOption('margin-top', $options['mt']);
        }

        if (isset($options['ml'])) {
            $pdf->setOption('margin-left', $options['ml']);
        }

        if (isset($options['mr'])) {
            $pdf->setOption('margin-right', $options['mr']);
        }

        if (isset($options['mb'])) {
            $pdf->setOption('margin-bottom', $options['mb']);
        }

        // Calidad y opciones de renderizado
        if (isset($options['low']) && $options['low'] == 1) {
            $pdf->setOption('lowquality', true);
        }

        if (isset($options['print']) && $options['print'] == 1) {
            $pdf->setOption('print-media-type', true);
        }

        if (isset($options['grayscale']) && $options['grayscale'] == 1) {
            $pdf->setOption('grayscale', true);
        }

        // Links y JavaScript
        $pdf->setOption('enable-external-links', true);
        $pdf->setOption('enable-internal-links', true);
        $pdf->setOption('no-stop-slow-scripts', true);

        $javascriptDelay = $options['javascript-delay'] ?? 1000;
        $pdf->setOption('javascript-delay', $javascriptDelay);

        // DPI y zoom
        if (isset($options['dpi'])) {
            $pdf->setOption('dpi', $options['dpi']);
        }

        $zoom = $options['zoom'] ?? 1;
        $pdf->setOption('zoom', $zoom);
    }
}
