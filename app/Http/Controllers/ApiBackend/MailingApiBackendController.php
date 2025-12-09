<?php

namespace App\Http\Controllers\ApiBackend;

use App\Models\Mailing;
use App\Mail\MailingMail;
use App\Services\MailerService;
use Illuminate\Http\Request;

/**
 * Set of operations used by Mailing private CrudController as API calls
 *
 * @author miquel
 */
class MailingApiBackendController
{

    /**
     * It sends a mailing testing to a given URL
     * Ahora usa exactamente el mismo flujo que el envío real
     * 
     * @param Request $request
     */
    public function test(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Buscar el mailing
        $mailing = Mailing::with('brand')->findOrFail($id);

        // Verificar brand ownership
        if ($mailing->brand_id !== get_current_brand()->id) {
            abort(403, 'No puedes enviar tests de mailings de otra brand');
        }

        // ✅ CARGAR CONTENIDO MANUALMENTE desde el disco
        // El Observer::retrieved() no funciona correctamente con eager loading
        $disk = \Storage::disk('mailings');
        $htmlFilename = $mailing->content_file_name . '.' . Mailing::HTML_EXTENSION;
        
        // Intentar buscar en la raíz primero (sistema nuevo)
        if ($disk->exists($htmlFilename)) {
            $content = $disk->get($htmlFilename);
        } 
        // Si no existe, intentar en subdirectorio de brand (sistema antiguo)
        elseif ($disk->exists($mailing->brand->code_name . '/' . $htmlFilename)) {
            $content = $disk->get($mailing->brand->code_name . '/' . $htmlFilename);
        }
        // Si tampoco, verificar en la ruta absoluta con brand (por si el disco está mal configurado)
        else {
            $absolutePath = storage_path("app/mailing/{$mailing->brand->code_name}/{$htmlFilename}");
            if (file_exists($absolutePath)) {
                $content = file_get_contents($absolutePath);
            } else {
                \Log::error('[MailingTest] Archivo HTML no encontrado', [
                    'mailing_id' => $mailing->id,
                    'filename' => $htmlFilename,
                    'brand_code' => $mailing->brand->code_name,
                    'tried_paths' => [
                        'disk_root' => $disk->path($htmlFilename),
                        'disk_brand' => $disk->path($mailing->brand->code_name . '/' . $htmlFilename),
                        'absolute' => $absolutePath,
                    ]
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo HTML del mailing no existe en ninguna ubicación conocida'
                ], 400);
            }
        }
        
        // Asignar el contenido al modelo
        $mailing->setAttribute('content', $content);

        $email = trim($request->get('email'));

        // Cambiar locale al del mailing
        config(['app.locale' => $mailing->locale]);

        try {
            // ✅ USAR EL MISMO FLUJO QUE EL ENVÍO REAL
            // Configurar el mailer para la brand específica (igual que SendMailingBatchJob)
            $mailerService = app(MailerService::class);
            $mailer = $mailerService->getMailerForBrand($mailing->brand);

            // Enviar usando MailingMail Mailable con isTest=true
            // Esto usará las mismas vistas, headers y configuración que el mail real
            $mailer->to($email)->send(new MailingMail($mailing, true));

            return response()->json([
                'success' => true,
                'message' => 'Email de prueba enviado a ' . $email
            ]);

        } catch (\Exception $e) {
            \Log::error('[MailingTest] Error enviando test', [
                'mailing_id' => $mailing->id,
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el email de prueba: ' . $e->getMessage()
            ], 500);
        }
    }
}