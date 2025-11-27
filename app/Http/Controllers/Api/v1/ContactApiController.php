<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Description of ContactController
 *
 * @author miquel
 */
class ContactApiController extends \App\Http\Controllers\Api\ApiController
{

    public function send(\Illuminate\Http\Request $request)
    {
        app()->setLocale($request->get('locale'));

        // ✅ Rate limiting: 5 envíos por hora por IP
        $key = 'contact_send:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('API: Contact form rate limit exceeded', ['ip' => request()->ip()]);

            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos. Por favor, espera ' . ceil($seconds / 60) . ' minutos.'
            ], 429);
        }
        RateLimiter::hit($key, 3600); // 1 hora

        // Validación de datos
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
            'recaptcha' => 'nullable|string'
        ]);

        // ✅ COMPATIBLE: Primero intenta brand_setting, luego config (como tincket-1)
        // Esto funciona tanto con tabla settings como con archivos de configuración
        $contactEmail = brand_setting('mail.contact.to') ?? config('mail.contact.to');

        if (!$contactEmail) {
            Log::warning('API: Contact form not enabled for this brand', [
                'brand_id' => $request->get('brand')->id ?? null,
                'brand_code' => $request->get('brand')->code_name ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El formulario de contacto no está disponible en este momento.'
            ], 403); // 403 Forbidden - servicio no disponible para esta brand
        }

        try {
            // ✅ COMPATIBLE: Usa el nuevo sistema de mailer (MailerService)
            // pero mantiene compatibilidad con la configuración antigua
            $mailer = app(\App\Services\MailerService::class)->getMailerForBrand($request->get('brand'));

            $mailer->alwaysReplyTo($request->get('email'), $request->get('name'));
            $mailer->alwaysFrom('noreply@yesweticket.com', 'YesWeTicket');

            // Email al administrador
            $mailerContent = new \App\Mail\GenericMailer();

            // ✅ COMPATIBLE: Intenta brand_setting, luego config
            $mailerContent->view = brand_setting('base.emails.contact')
                ?? config('base.emails.contact')
                ?? 'emails.contact'; // Fallback

            $mailerContent->subject = brand_setting('mail.contact.subject')
                ?? config('mail.contact.subject')
                ?? 'Contacte des del web';

            $mailerContent->viewData = [
                'content' => $request->get('message'),
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'recaptcha' => $request->get('recaptcha') ?? null
            ];

            // Enviar email al administrador
            $mailer->to(explode(",", $contactEmail))->send($mailerContent);

            // Email de notificación al usuario
            $notificationView = brand_setting('core.emails.contact-notification')
                ?? config('core.emails.contact-notification')
                ?? 'core.emails.contact-notification';

            if ($notificationView) {
                $mailerNotification = new \App\Mail\GenericMailer();
                $mailerNotification->view = $notificationView;
                $mailerNotification->subject = brand_setting('mail.contact.notification_subject')
                    ?? config('mail.contact.notification_subject')
                    ?? 'Formulari contacte';

                $mailer->to(trim($request->get('email')))->send($mailerNotification);
            }
        } catch (\Exception $e) {
            Log::error('API: Contact form send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'brand_id' => $request->get('brand')->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje. Por favor, inténtalo de nuevo más tarde.'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Mensaje enviado correctamente. Te responderemos lo antes posible.'
        ]);
    }
}
