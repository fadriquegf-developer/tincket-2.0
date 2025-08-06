<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use App\Models\Pack;
use App\Models\Client;
use App\Models\Session;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\TicketOfficeRequest;
use Illuminate\Http\RedirectResponse;

class TicketOfficeController extends Controller
{

    
    public function create()
    {
        $entry = new Cart();

        $builder = Session::query()->with('rates', 'event', 'space')
            ->orderBy('starts_on');

        if (!request()->get('show_expired', false)) {
            $builder->where('ends_on', '>', \Carbon\Carbon::now()); // end of session
        }

        $sessions = $builder->get();

        $builder_packs = Pack::query();

        if (!request()->get('show_expired', false)) {
            $builder_packs->where('starts_on', '<=', \Carbon\Carbon::now())
                ->where(function ($query) {
                    $query->whereNull('ends_on')
                        ->orWhere('ends_on', '>', \Carbon\Carbon::now()); // end of session
                });
        }

        $packs = $builder_packs->get();

        $sessions->each(function ($s) {
            $s->rates->map(function ($rate) {
                $rate->setAttribute('rate_name', $rate->name);
            });

            //$s->setRelation('rates', $rates);
        });

        return view('core.ticket-office.create', compact('entry', 'sessions', 'packs'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TicketOfficeRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TicketOfficeRequest $request): RedirectResponse
    {
        try {
            $cart = (new \App\Services\Api\CartService())->createCart($request);

            $this->associateClient($request, $cart);

            $this->createInscriptions($request, $cart);

            $this->createPacks($request, $cart);

            $this->createGiftCarts($request, $cart);

            $this->confirmCart($cart);
        } catch (\App\Exceptions\ApiException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        } catch (\Exception $e) {
            throw $e;
        }

        \Illuminate\Support\Facades\Session::flash('download_all_inscriptions', true);

        return redirect()->route('crud.cart.show', ['cart' => $cart->id]);
    }

    private function associateClient(TicketOfficeRequest $request, $cart)
    {
        if ($request->get('client_email')) {
            $client = Client::query()
                ->where('email', $request->get('client_email'))
                ->first();

            if (!$client) {
                $client = new Client([
                    'email' => $request->get('client_email'),
                    'name' => $request->get('client_firstname'),
                    'surname' => $request->get('client_lastname'),
                    'locale' => config('app.locale')
                ]);
                $client->brand()->associate($request->get('brand'));
                $client->save();
            }
            $cart->client()->associate($client)->save();
        }
    }

    private function createInscriptions(TicketOfficeRequest $request, Cart $cart)
    {
        $inscription_service = new \App\Services\Api\InscriptionService();
        $inscription_service->enablePrivateUsage();
        \DB::beginTransaction();

        $params = [];
        try {
            // group inscriptions sessions to prevent autolock lock selected slots
            foreach ($request->input('inscriptions.session_id', []) as $index => $value) {
                if ($value) {
                    if (array_key_exists($value, $params)) {
                        // increse slots
                        if ($params[$value]['is_numbered']) {
                            // numered
                            $params[$value]['inscriptions'][] = [
                                'is_numbered' => $params[$value]['is_numbered'],
                                'rate_id' => $request->input("inscriptions.rate_id")[$index],
                                'slot_id' => $request->input("inscriptions.slot_id")[$index],
                                'quantity' => 1
                            ];
                        } else {
                            // no numered
                            // search rate
                            $aux = array_search($request->input("inscriptions.rate_id")[$index], array_column($params[$value]['inscriptions'], 'rate_id'));

                            if ($aux === false) {
                                // new rate
                                $params[$value]['inscriptions'][] = [
                                    'is_numbered' => $params[$value]['is_numbered'],
                                    'rate_id' => $request->input("inscriptions.rate_id")[$index],
                                    'quantity' => 1
                                ];
                            } else {
                                // increce
                                $params[$value]['inscriptions'][$aux]['quantity']++;
                            }
                        }
                    } else {
                        // new session
                        $session = Session::query()->findOrFail($value);
                        $params[$value] = [
                            'cart_id' => $cart->id,
                            'session_id' => $value,
                            'is_numbered' => (bool) $session->is_numbered,
                            'inscriptions' => [
                                [
                                    'is_numbered' => (bool) $session->is_numbered,
                                    'rate_id' => $request->input("inscriptions.rate_id")[$index],
                                    'slot_id' => $request->input("inscriptions.slot_id")[$index],
                                    'quantity' => 1
                                ]
                            ]
                        ];
                    }
                }
            }

            // create all insscriptions foreach session
            foreach ($params as $param) {
                $inscription_service->createNewInscriptionsSet($param);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            return redirect()->back()->withErrors(['Something is wrong with inscriptions. Check all fields']);
        }

        \DB::commit();
    }

    private function createPacks(TicketOfficeRequest $request, Cart $cart)
    {
        $cart_service = new \App\Services\Api\CartService();
        $cart_service->enablePrivateUsage();
        \DB::beginTransaction();
        $groupPacks = [];
        $params = [];
        // TODO: imnprove angular creator pack and return data in correct format to avoid transform data
        try {
            // group packs to prevent autolock lock selected slots same session
            foreach ($request->input('packs', []) as $index => $rawPack) {
                if ($rawPack) {
                    $pack = json_decode($rawPack);

                    if (array_key_exists($pack->pack_id, $groupPacks)) {
                        // increse inscriptions
                        $groupPacks[$pack->pack_id]['pack_multiplier']++;
                    } else {
                        // new pack
                        $groupPacks[$pack->pack_id]['pack_multiplier'] = 1;
                        $groupPacks[$pack->pack_id]['selection'] = [];
                    }

                    foreach ($pack->selection as $s) {

                        if (!array_key_exists($s->session_id, $groupPacks[$pack->pack_id]['selection'])) {
                            //new session
                            $groupPacks[$pack->pack_id]['selection'][$s->session_id]['is_numbered'] = Session::find($s->session_id)->is_numbered;
                        }

                        if ($s->slot_id != null) {
                            $groupPacks[$pack->pack_id]['selection'][$s->session_id]['slots'][] = $s->slot_id;
                        } else {
                            $groupPacks[$pack->pack_id]['selection'][$s->session_id]['slots'] = null;
                        }
                    }
                }
            }

            // prepare parameters
            foreach ($groupPacks as $pack_id => $p) {
                $params[$pack_id] = [
                    'pack_id' => $pack_id,
                    'pack_multiplier' => $p['pack_multiplier'],
                    'selection' => []
                ];

                foreach ($p['selection'] as $session_id => $session) {
                    $params[$pack_id]['selection'][] = [
                        'session_id' => $session_id,
                        'is_numbered' => $session['is_numbered'],
                        'slots' => $session['slots'],
                        'quantity' => 1
                    ];
                }
            }

            foreach ($params as $param) {
                // ugly, would be better to pass array to the service instead of request
                $request->merge($param);
                $cart_service->setPack($cart, $request);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        DB::commit();
    }

    /**
     * Create inscription releted to gift card
     */
    private function createGiftCarts(TicketOfficeRequest $request, Cart $cart)
    {
        $inscription_service = new \App\Services\Api\InscriptionService();
        $inscription_service->enablePrivateUsage();
        DB::beginTransaction();

        $params = [];
        try {
            // group inscriptions sessions to prevent autolock lock selected slots
            foreach ($request->input('gift_cards.session_id', []) as $index => $value) {
                if ($value) {
                    if (array_key_exists($value, $params)) {
                        // add slots
                        $params[$value]['inscriptions'][] = [
                            'is_numbered' => $params[$value]['is_numbered'],
                            'rate_id' =>  $params[$value]['inscriptions'][0]['rate_id'],
                            'slot_id' => $request->input("gift_cards.slot_id")[$index],
                            'gift_code' => $request->input("gift_cards.code")[$index],
                            'quantity' => 1
                        ];
                    } else {
                        // new session
                        $session = Session::query()->findOrFail($value);
                        $params[$value] = [
                            'cart_id' => $cart->id,
                            'session_id' => $value,
                            'is_numbered' => (bool) $session->is_numbered,
                            'inscriptions' => [
                                [
                                    'is_numbered' => (bool) $session->is_numbered,
                                    'rate_id' => $session->generalRate->rate_id,
                                    'slot_id' => $request->input("gift_cards.slot_id")[$index],
                                    'gift_code' => $request->input("gift_cards.code")[$index],
                                    'quantity' => 1
                                ]
                            ]
                        ];
                    }
                }
            }

            // create all insscriptions foreach session
            foreach ($params as $param) {
                $inscription_service->createNewInscriptionsSet($param);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            return redirect()->back()->withErrors(['Something is wrong with gift card inscriptions. Check all fields']);
        }

        DB::commit();
    }

    private function confirmCart(Cart $cart)
    {
        $payment_service = \App\Services\Payment\PaymentServiceFactory::create('TicketOffice');
        $payment_service->setPaymentType(request()->get('payment_type'));
        $payment_service->purchase($cart);
        $payment_service->confirmPayment();
    }
}
