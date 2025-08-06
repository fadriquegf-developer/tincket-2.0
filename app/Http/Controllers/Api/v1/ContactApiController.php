<?php

namespace App\Http\Controllers\Api\v1;

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

        // Validación de datos
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string|max:5000',
            'recaptcha' => 'nullable|string'
        ]);

        if (!config('mail.contact.to'))
            abort(403);

        $mailer = (new \App\Services\MailerBrandService(request()->get('brand')->code_name))->getMailer();

        $mailer->alwaysReplyTo($request->get('email'), $request->get('name'));
        $mailer->alwaysFrom('noreply@yesweticket.com', 'YesWeTicket');

        // send email admin
        $mailerContent = new \App\Mail\GenericMailer();
        $mailerContent->view = config('base.emails.contact');
        $mailerContent->subject = config('mail.contact.subject', "Contacte des del web");
        $mailerContent->viewData = [
            'content' => $request->get('message'),
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'recaptcha' => $request->get('recaptcha') ?? null
        ];

        try {
            // send email admin
            $mailer->to(explode(",", config('mail.contact.to')))->send($mailerContent);

            // Send email user
            $mailerNotification = new \App\Mail\GenericMailer();
            $mailerNotification->view = 'core.emails.contact-notification';
            $mailerNotification->subject = config('mail.contact.subject', "Formulari contacte");
            $mailer->to(trim($request->get('email')))->send($mailerNotification);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('tincket.contact.send_error')
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => trans('tincket.contact.sent_success')
        ]);
    }
}
