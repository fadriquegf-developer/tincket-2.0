<?php

namespace App\Http\Controllers\Open;

use App\Http\Controllers\Controller;

class InscriptionController extends Controller
{

    /**
     * This methods generates an endpoint to print an A4 HTML ticket page.
     *
     * To access to this method the route need the ID of the inscription
     * and the token of the associated order to get it.
     *
     * It will only be accessible while Inscription does not have pdf generated
     * so a non allowed access is unlikely because the generation of PDF
     * will be triggered after the buying process succes.
     *
     * @param \App\Models\Inscription $inscription
     */
    public function getInscriptionTicket($id)
    {
        $inscription = \App\Models\Inscription::with([
            'cart.brand',
            'cart.client',
            'session',
            'session.event',
            'session.space',
            'session.space.location',
            'session.space.location.city',
            'group_pack.pack',
            'slot',
        ])->find($id); 

        \Log::info('Inscription ticket requested', [
            'inscription_id' => $id,
            'town' => $inscription->session->space->location->city->name ?? null,
        ]);

        if (
            !$inscription->exists
            || !$inscription->cart->confirmation_code
        ) {
            abort(404, "PDF cannot be rendered");
        }

        // Cargar la configuración de brand
        $middleware = new \App\Http\Middleware\CheckBrandHost();
        $middleware->loadBrandConfig($inscription->cart->brand->code_name);

        // Idioma cliente
        \App::setLocale($inscription->cart->client->locale ?? config('app.locale'));

        $view = brand_setting('base.inscription.view.ticket');
        $ticketOfficeView = brand_setting('base.inscription.view.ticket-office');

        if (
            ($inscription->cart->seller_type === 'App\Models\User' && !request()->has('web'))
            || request()->get('ticket-office') == 1
        ) {
            if ($ticketOfficeView) {
                $view = $ticketOfficeView;
            }
        }

        $testPreview = brand_setting('base.inscription.view.ticket-test');
        if (request()->get('test') == 1 && $testPreview) {
            $view = $testPreview;
        }

        return \View::make($view, compact('inscription'));
    }
}
