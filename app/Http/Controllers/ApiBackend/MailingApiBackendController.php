<?php

namespace App\Http\Controllers\ApiBackend;

use App\Models\Mailing;
use App\Mail\MailingMail;
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
     * @param Request $request
     */
    public function test(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Buscar el mailing manualmente
        $mailing = Mailing::findOrFail($id);

        // Cargar contenido manualmente sin usar el disco
        $htmlPath = storage_path("app/mailing/{$mailing->brand->code_name}/{$mailing->content_file_name}.html");

        // Verificar brand ownership
        if ($mailing->brand_id !== get_current_brand()->id) {
            abort(403, 'No puedes enviar tests de mailings de otra brand');
        }

        if (!file_exists($htmlPath)) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo HTML no existe'
            ], 400);
        }

        $email = trim($request->get('email'));

        // Cambiar locale al del mailing
        config(['app.locale' => $mailing->locale]);

        $content = file_get_contents($htmlPath);

        // Enviar directamente el HTML
        \Mail::send([], [], function ($message) use ($mailing, $email, $content) {
            $message->to($email)
                ->subject('[TEST] ' . $mailing->subject)
                ->html($content);
        });

        return response()->json([
            'success' => true,
            'message' => 'Email de prueba enviado a ' . $email
        ]);
    }
}
