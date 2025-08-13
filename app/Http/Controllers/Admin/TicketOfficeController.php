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
use Illuminate\View\View;
use Illuminate\Http\Request;

class TicketOfficeController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $entry = new Cart();
        $builder = Session::query()->with('rates', 'event', 'space')
            ->orderBy('starts_on');

        if (!$request->get('show_expired', false)) {
            $builder->where('ends_on', '>', \Carbon\Carbon::now());
        }

        $sessions = $builder->get();

        $builder_packs = Pack::query();

        if (!$request->get('show_expired', false)) {
            $builder_packs->where('starts_on', '<=', \Carbon\Carbon::now())
                ->where(function ($query) {
                    $query->whereNull('ends_on')
                        ->orWhere('ends_on', '>', \Carbon\Carbon::now());
                });
        }

        $packs = $builder_packs->get();

        $sessions->each(function ($s) {
            $s->rates->map(function ($rate) {
                $rate->setAttribute('rate_name', $rate->name);
                return $rate;
            });
        });

        // Procesar datos antiguos si existen
        $old_data = $this->processOldData($request);

        // Calcular si debemos obtener posiciones libres
        $calculeFreePositions = $sessions->count() < 100;

        $json_sessions = $this->prepareSessionsJson($sessions, $calculeFreePositions);

        return view('core.ticket-office.create', compact(
            'entry',
            'sessions',
            'packs',
            'old_data',
            'calculeFreePositions',
            'json_sessions'
        ));
    }

    /**
     * Procesa los datos antiguos del formulario
     */
    private function processOldData(Request $request): array
    {
        if (!$request->old()) {
            return [];
        }

        $old_data = [
            "client" => [
                "email" => $request->old('client.email'),
                "firstname" => $request->old('client.firstname'),
                "lastname" => $request->old('client.lastname')
            ],
            "inscriptions" => []
        ];

        foreach ($request->old('inscriptions.session_id', []) as $key => $value) {
            $old_data['inscriptions'][] = [
                'session_id' => $request->old('inscriptions.session_id')[$key] ?? '',
                'rate_id' => $request->old('inscriptions.rate_id')[$key] ?? '',
                'quantity' => $request->old('inscriptions.quantity')[$key] ?? 0,
            ];
        }

        return $old_data;
    }

    /**
     * Prepara los datos de sesiones para JSON
     */
    private function prepareSessionsJson($sessions, bool $calculeFreePositions): \Illuminate\Support\Collection
    {
        return $sessions->map(function ($session) use ($calculeFreePositions) {
            return [
                'id' => $session->id,
                'name' => sprintf("%s %s (%s)", $session->event->name, $session->name, $session->starts_on->format('d/m/Y H:i')),
                'is_numbered' => $session->is_numbered,
                'is_past' => $session->ends_on < \Carbon\Carbon::now(),
                'rates' => $session->rates->map(function ($rate) use ($calculeFreePositions) {
                    $max_per_order = $calculeFreePositions ? $rate->count_free_positions : 0;
                    return [
                        'id' => $rate->id,
                        'name' => [
                            config('app.locale') => $rate->name
                        ],
                        'available' => max(0, $max_per_order),
                        'price' => $rate->pivot->price
                    ];
                }),
                'space' => [
                    'layout' => $session->space?->svg_path ? asset(\Storage::url($session->space->svg_path)) : '',
                    'zoom' => $session->space?->zoom ?? false
                ]
            ];
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketOfficeRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $cart = (new \App\Services\Api\CartService())->createCart($request);

            $this->associateClient($request, $cart);
            $this->createInscriptions($request, $cart);
            $this->createPacks($request, $cart);
            $this->createGiftCarts($request, $cart);
            $this->confirmCart($cart);

            DB::commit();
        } catch (\App\Exceptions\ApiException $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([$e->getMessage()]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        \Illuminate\Support\Facades\Session::flash('download_all_inscriptions', true);

        return redirect()->route('crud.cart.show', ['id' => $cart->id]);
    }

    private function associateClient(TicketOfficeRequest $request, Cart $cart): void
    {
        $email = $request->input('client.email');

        if ($email) {
            $client = Client::query()
                ->where('email', $email)
                ->first();

            if (!$client) {
                $client = new Client([
                    'email' => $email,
                    'name' => $request->input('client.firstname'),
                    'surname' => $request->input('client.lastname'),
                    'locale' => config('app.locale')
                ]);
                $client->brand()->associate($request->get('brand'));
                $client->save();
            }

            $cart->client()->associate($client)->save();
        }
    }

    private function createInscriptions(TicketOfficeRequest $request, Cart $cart): void
    {
        $inscription_service = new \App\Services\Api\InscriptionService();
        $inscription_service->enablePrivateUsage();

        $params = [];

        try {
            // Agrupar inscripciones por sesión para prevenir bloqueos
            foreach ($request->input('inscriptions.session_id', []) as $index => $value) {
                if ($value) {
                    if (array_key_exists($value, $params)) {
                        // Incrementar slots
                        if ($params[$value]['is_numbered']) {
                            // Numerada
                            $params[$value]['inscriptions'][] = [
                                'is_numbered' => $params[$value]['is_numbered'],
                                'rate_id' => $request->input("inscriptions.rate_id")[$index],
                                'slot_id' => $request->input("inscriptions.slot_id")[$index],
                                'quantity' => 1
                            ];
                        } else {
                            // No numerada
                            $rateId = $request->input("inscriptions.rate_id")[$index];
                            $aux = array_search($rateId, array_column($params[$value]['inscriptions'], 'rate_id'));

                            if ($aux === false) {
                                // Nueva tarifa
                                $params[$value]['inscriptions'][] = [
                                    'is_numbered' => $params[$value]['is_numbered'],
                                    'rate_id' => $rateId,
                                    'quantity' => 1
                                ];
                            } else {
                                // Incrementar cantidad
                                $params[$value]['inscriptions'][$aux]['quantity']++;
                            }
                        }
                    } else {
                        // Nueva sesión
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

            // Crear todas las inscripciones para cada sesión
            foreach ($params as $param) {
                $inscription_service->createNewInscriptionsSet($param);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function createPacks(TicketOfficeRequest $request, Cart $cart): void
    {
        $cart_service = new \App\Services\Api\CartService();
        $cart_service->enablePrivateUsage();

        $groupPacks = [];
        $params = [];

        try {
            // Agrupar packs para prevenir bloqueos
            foreach ($request->input('packs', []) as $index => $rawPack) {
                if ($rawPack) {
                    $pack = json_decode($rawPack);

                    if (array_key_exists($pack->pack_id, $groupPacks)) {
                        $groupPacks[$pack->pack_id]['pack_multiplier']++;
                    } else {
                        $groupPacks[$pack->pack_id]['pack_multiplier'] = 1;
                        $groupPacks[$pack->pack_id]['selection'] = [];
                    }

                    foreach ($pack->selection as $s) {
                        if (!array_key_exists($s->session_id, $groupPacks[$pack->pack_id]['selection'])) {
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

            // Preparar parámetros
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
                $request->merge($param);
                $cart_service->setPack($cart, $request);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create inscription related to gift card
     */
    private function createGiftCarts(TicketOfficeRequest $request, Cart $cart): void
    {
        $inscription_service = new \App\Services\Api\InscriptionService();
        $inscription_service->enablePrivateUsage();

        $params = [];

        try {
            // Agrupar inscripciones por sesión
            foreach ($request->input('gift_cards.session_id', []) as $index => $value) {
                if ($value) {
                    if (array_key_exists($value, $params)) {
                        $params[$value]['inscriptions'][] = [
                            'is_numbered' => $params[$value]['is_numbered'],
                            'rate_id' => $params[$value]['inscriptions'][0]['rate_id'],
                            'slot_id' => $request->input("gift_cards.slot_id")[$index],
                            'gift_code' => $request->input("gift_cards.code")[$index],
                            'quantity' => 1
                        ];
                    } else {
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

            foreach ($params as $param) {
                $inscription_service->createNewInscriptionsSet($param);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function confirmCart(Cart $cart): void
    {
        $payment_service = \App\Services\Payment\PaymentServiceFactory::create('TicketOffice');
        $payment_service->setPaymentType(request()->get('payment_type'));
        $payment_service->purchase($cart);
        $payment_service->confirmPayment();
    }
}
