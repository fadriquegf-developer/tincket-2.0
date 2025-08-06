<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Cart as Cart;
use App\Models\GroupPack;
use App\Models\Session;
use App\Models\Inscription;
use App\Services\Api\CartService;
use App\Services\Payment\PaymentServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use \Carbon\Carbon as Carbon;

class CartApiController extends \App\Http\Controllers\Api\ApiController
{

    /** @var CartService */
    private $service;
    private $paymentServiceFactory;

    public function __construct()
    {
        $this->service = new CartService;
        $this->paymentServiceFactory = new PaymentServiceFactory;

        // Checks if Cart is expired and updates the expires_on date        
        $this->middleware(\App\Http\Middleware\Api\v1\CartExpiration::class)->only(['update', 'destroy', 'extendTime']);
        // Checks if Cart is confirmed before interacting with it
        $this->middleware(\App\Http\Middleware\Api\v1\CartConfirmation::class)->only(['update', 'destroy', 'extendTime']);
    }

    public function show($id)
    {

        $cart = $this->getCartBuilder($id)->with(
            'inscriptions.slot',
            'inscriptions.session.event',
            'inscriptions.session.space.location',
            'inscriptions.rate',
            'groupPacks.pack',
            'groupPacks.inscriptions.session.event',
            'groupPacks.inscriptions.session.space.location',
            'groupPacks.inscriptions.slot',
            'gift_cards.event'
        )
            ->firstOrFail();

        // we manage data to a more readable way
        $cart->setAttribute('packs', $cart->groupPacks->map(function ($c) {
            // we clone it since $c->pack may refer to 
            // same object and cart_pack_id is unique 
            // in each row            
            $p = clone $c->pack;

            $p->setAttribute('cart_pack_id', $c->id);
            $p->setAttribute('inscriptions', $c->inscriptions);

            return $p;
        }));

        unset($cart->groupPacks);

        return $this->json($cart);
    }

    public function store(Request $request)
    {
        try {
            $cart = $this->service->createCart($request);
            return $this->show($cart->id);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Cart $cart, $section, Request $request)
    {
        if (method_exists($this->service, 'set' . Str::studly($section))) {
            $data = $this->service->{'set' . Str::studly($section)}($cart, $request);
        } else {
            abort(404, "Method set" . Str::studly($section) . " not found in CartService");
        }

        return $this->json($data, $data ? 200 : 204);
    }

    public function destroy(Cart $cart, $type, $id)
    {
        try {
            switch ($type) {
                case 'pack':
                    return $this->destroyPack($cart, GroupPack::find($id));
                case 'session':
                    return $this->destroySession($cart, Session::find($id));
                case 'inscription':
                    return $this->destroyInscription($cart, Inscription::find($id));
                case 'gift-card':
                    return $this->destroyGiftCard($cart, $id);
                default:
                    return $this->json(['error' => "Type $type is not expected"], 400);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    private function destroyPack(Cart $cart, GroupPack $pack)
    {
        if (!$pack) {
            return $this->json(['error' => 'Pack not found'], 404);
        }

        $pack->inscriptions->each(function (Inscription $inscription) {
            $inscription->delete();
        });
        $pack->delete();

        return $this->json(null, 204);
    }


    private function destroyInscription(Cart $cart, Inscription $inscription)
    {

        if ($inscription->rate()->get()->first()->has_rule) {
            $cart->inscriptions()
                ->where('rate_id', '=', $inscription->rate()->get()->first()->id)
                ->where('session_id', '=', $inscription->session()->get()->first()->id)
                ->get()->each(function (Inscription $inscription) {
                    $inscription->delete();
                });
        } else {
            $inscription->delete();
        }

        return $this->json(null, 204);
    }

    private function destroySession(Cart $cart, Session $session)
    {
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        // Eliminar todas las inscripciones en una sola operación
        $cart->inscriptions()->where('session_id', $session->id)->delete();

        return $this->json(null, 204);
    }


    private function destroyGiftCard(Cart $cart, $id)
    {
        $cart->gift_cards()->where('id', $id)->delete();

        return $this->json(null, 204);
    }

    public function checkDuplicated($id)
    {
        $cart = $this->getCartBuilder($id)
            ->notExpired()
            ->whereNull('confirmation_code')
            ->withInscriptions()
            ->orHas('gift_cards')
            ->firstOrFail();

        $slots = $cart->allInscriptions->pluck('slot_id');
        $session_id = $cart->allInscriptions->first()->session_id ?? '';

        $duplicated_cart = Cart::ownedByBrand()->notExpired()
            ->where('id', '!=', $cart->id)
            ->whereHas('allInscriptions', function ($q) use ($session_id, $slots) {
                $q->where('session_id', $session_id)->whereIn('slot_id', $slots);
            })
            ->first();

        if ($duplicated_cart)
            return $this->json(true, 200);

        return $this->json(false, 200);
    }


    public function getPayment($id)
    {

        $builder = $this->getCartBuilder($id)
            ->notExpired()
            ->whereNull('confirmation_code')
            ->where(function ($q) {
                $q->withInscriptions()
                    ->orWhereHas('gift_cards');
            });

        $cart = $builder->first();

        try {
            // Determinar la plataforma de pago
            $platform = $cart->price_sold == 0 ? 'Free' : 'Redsys_Redirect';

            // Crear el servicio de pago usando la fábrica
            $service = $this->paymentServiceFactory->create($platform);

            // Procesar la compra
            $service->purchase($cart);

            return $this->json($service->getData());
        } catch (\Exception $e) {
            // Registrar el error para fines de depuración
            \Log::error('PAYMENT error', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retornar una respuesta de error al cliente
            return $this->json(['error' => 'No se pudo procesar el pago.'], 500);
        }
    }

    public function getPaymentForEmail($token)
    {
        $cart = $this->getCartBuilder($token)->with('payments')->first();

        $platform = 'Redsys_Redirect';

        $service = $this->paymentServiceFactory->create($platform);

        $service->purchase($cart);

        return $this->json($service->getData());
    }

    public function checkPaymentPaid($token)
    {
        $cart = $this->getCartBuilder($token)->first();
        if ($cart->payments()->where('paid_at', '!=', NULL)->count() > 0) {
            return $this->json(true);
        }

        return $this->json(null, 204);
    }

    public function extendTime(Cart $cart)
    {
        // time is extended in Middleware. If time is expired, it throws an Exception
        // and next return is not reached
        return $this->json(null, 204);
    }

    public function expiredTime(Cart $cart)
    {
        $cart->expiredTime();

        return $this->json(null, 204);
    }

    // https://gitlab.com/javajan_laravel/tincket/issues/52
    //
    // This method should be REfactored when this issue is fixed.
    private function getCartBuilder($id)
    {

        if (is_numeric($id)) {
            return Cart::ownedByBrand()
                ->where('id', $id)
                ->with('brand');
        }

        return Cart::ownedByBrand()
            ->where('token', $id)
            ->with('brand');
    }
}
