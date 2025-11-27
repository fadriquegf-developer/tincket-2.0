<?php

namespace App\Http\Controllers\Open;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inscription;

class InscriptionController extends Controller
{
    public function getInscriptionTicket($id, Request $request)
    {
        try {
            $inscription = Inscription::withoutGlobalScopes()
                ->with([
                    'cart' => fn($q) => $q->withoutGlobalScopes()->with([
                        'brand'  => fn($q) => $q->withoutGlobalScopes(),
                        'client' => fn($q) => $q->withoutGlobalScopes(),
                    ]),
                    'session' => fn($q) => $q->withoutGlobalScopes()->with([
                        'event' => fn($q) => $q->withoutGlobalScopes(),
                        'space' => fn($q) => $q->withoutGlobalScopes()->with([
                            'location' => fn($q) => $q->withoutGlobalScopes()->with([
                                'city' => fn($q) => $q->withoutGlobalScopes(),
                            ]),
                        ]),
                    ]),
                    'group_pack' => fn($q) => $q->withoutGlobalScopes()->with([
                        'pack' => fn($q) => $q->withoutGlobalScopes(),
                    ]),
                    'slot' => fn($q) => $q->withoutGlobalScopes(),
                ])
                ->findOrFail($id);

            // Verificar token PRIMERO
            $token = $request->get('token');
            if (!$token || $inscription->cart->token !== $token) {
                abort(403, "Invalid token");
            }

            $brandCode = $request->get('brand_code') ?? $inscription->cart->brand->code_name;

            // Cargar configuración de brand
            $middleware = new \App\Http\Middleware\CheckBrandHost();
            $middleware->loadBrandConfig($brandCode);

            // Verificar confirmación DESPUÉS de cargar brand
            if (!$inscription->cart->confirmation_code) {
                abort(404, "PDF cannot be rendered - cart not confirmed");
            }

            \App::setLocale($inscription->cart->client->locale ?? config('app.locale'));

            $view = brand_setting('base.inscription.view.ticket');
            $ticketOfficeView = brand_setting('base.inscription.view.ticket-office');

            \Log::info("getInscriptionTicket cart({$inscription->cart->id}) brand({$brandCode}) view: {$view}. ticketOfficeView: {$ticketOfficeView}");

            if (
                ($inscription->cart->seller_type === 'App\Models\User' && !$request->has('web'))
                || $request->get('ticket-office') == 1
            ) {
                if ($ticketOfficeView) {
                    $view = $ticketOfficeView;
                }
            }

            $testPreview = brand_setting('base.inscription.view.ticket-test');
            if ($request->get('test') == 1 && $testPreview) {
                $view = $testPreview;
            }

            if (!view()->exists($view)) {
                abort(500, "Template not found: {$view}");
            }

            return \View::make($view, compact('inscription'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, "Inscription not found");
        }
    }
}
