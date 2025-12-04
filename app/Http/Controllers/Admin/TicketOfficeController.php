<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use App\Models\Pack;
use App\Models\Client;
use App\Models\Session;
use App\Models\Inscription;
use App\Services\InscriptionService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\TicketOfficeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

class TicketOfficeController extends Controller
{
    public function index()
    {
        return redirect()->route('ticket-office.create');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $entry = new Cart();
        $builder = Session::query()->with(['allRates.rate', 'event', 'space'])
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

        $packs = $builder_packs->orderBy('starts_on')->get();

        $sessions->each(function ($s) {
            $s->allRates->map(function ($assignatedRate) {
                if ($assignatedRate->rate) {
                    $assignatedRate->rate->setAttribute('rate_name', $assignatedRate->rate->name);
                }
                return $assignatedRate;
            });
        });

        $old_data = $this->processOldData($request);
        $calculeFreePositions = $sessions->count() < 100;
        $json_sessions = $this->prepareSessionsJson($sessions, $calculeFreePositions);
        $translations = __('ticket-office');

        return view('core.ticket-office.create', compact(
            'entry',
            'sessions',
            'packs',
            'old_data',
            'calculeFreePositions',
            'json_sessions',
            'translations'
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
            $rates = $session->allRates()
                ->with('rate')
                ->get()
                ->map(function ($assignatedRate) use ($calculeFreePositions) {
                    $max_per_order = $calculeFreePositions ? $assignatedRate->count_free_positions : 0;

                    return [
                        'id' => $assignatedRate->rate->id ?? '',
                        'name' => $assignatedRate->rate->name ?? '',
                        'available' => max(0, $max_per_order),
                        'price' => $assignatedRate->price ?? 0,
                        'max_per_order' => $assignatedRate->max_per_order ?? 1,
                    ];
                });

            return [
                'id' => $session->id,
                'name' => sprintf(
                    "%s %s (%s)",
                    $session->event->name,
                    $session->name,
                    $session->starts_on->format('d/m/Y H:i')
                ),
                'is_numbered' => $session->is_numbered,
                'is_past' => $session->ends_on < \Carbon\Carbon::now(),
                'rates' => $rates,
                'free_positions' => $calculeFreePositions ? $session->getFreePositions() : 0,
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

        return redirect()->route('cart.show', ['id' => $cart->id]);
    }

    private function associateClient(TicketOfficeRequest $request, Cart $cart): void
    {
        $email = $request->input('client.email');

        if ($email) {
            $client = Client::query()
                ->where('email', $email)
                ->first();

            if (!$client) {
                // 🔧 FIX: Obtener brand correctamente
                $brand = get_current_brand()
                    ?? $request->attributes->get('brand')
                    ?? $request->get('brand');

                $client = new Client([
                    'email' => $email,
                    'name' => $request->input('client.firstname'),
                    'surname' => $request->input('client.lastname'),
                    'locale' => config('app.locale')
                ]);
                $client->brand()->associate($brand);
                $client->save();
            }

            $cart->client()->associate($client)->save();
        }
    }

    /**
     * 🔧 FIX: Reescrito completamente para usar InscriptionService correctamente
     */
    private function createInscriptions(TicketOfficeRequest $request, Cart $cart): void
    {
        $inscriptionService = new InscriptionService();

        try {
            // Agrupar inscripciones por sesión
            $inscriptionsBySession = [];

            foreach ($request->input('inscriptions.session_id', []) as $index => $sessionId) {
                if (!$sessionId) {
                    continue;
                }

                if (!isset($inscriptionsBySession[$sessionId])) {
                    $inscriptionsBySession[$sessionId] = [];
                }

                $inscriptionsBySession[$sessionId][] = [
                    'rate_id' => $request->input("inscriptions.rate_id")[$index],
                    'slot_id' => $request->input("inscriptions.slot_id")[$index] ?? null,
                ];
            }

            //  VALIDAR LÍMITES POR USUARIO ANTES DE CREAR INSCRIPCIONES
            $clientEmail = $cart->client ? $cart->client->email : $request->input('client.email');

            if ($clientEmail) {
                foreach ($inscriptionsBySession as $sessionId => $inscriptionsData) {
                    $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
                        ->findOrFail($sessionId);

                    // Solo validar si la sesión tiene límite activo
                    if ($session->limit_per_user) {
                        // Contar inscripciones ya compradas por este email
                        $inscriptionsBuyed = Inscription::withoutGlobalScope(\App\Scopes\BrandScope::class)
                            ->where('session_id', $session->id)
                            ->whereHas('cart', function ($q) use ($clientEmail) {
                                $q->whereHas('client', function ($clientQ) use ($clientEmail) {
                                    $clientQ->where('email', $clientEmail);
                                })
                                    ->whereHas('payments', function ($paymentQ) {
                                        $paymentQ->whereNotNull('paid_at');
                                    });
                            })
                            ->count();

                        // Contar cuántas está intentando añadir ahora
                        $cantidadAañadir = count($inscriptionsData);
                        $totalFinal = $inscriptionsBuyed + $cantidadAañadir;

                        $sessionName = $session->event->name ?? '';
                        if ($session->name) {
                            $sessionName .= ' - ' . $session->name;
                        }
                        $sessionName .= ' (' . $session->starts_on->format('d/m/Y H:i') . ')';

                        if ($totalFinal > $session->max_per_user) {
                            $disponibles = $session->max_per_user - $inscriptionsBuyed;
                            throw new \App\Exceptions\ApiException(
                                __('ticket-office.errors.limit_per_user_exceeded', [
                                    'session' => $sessionName,
                                    'buyed' => $inscriptionsBuyed,
                                    'max' => $session->max_per_user,
                                    'available' => $disponibles
                                ])
                            );
                        }
                    }
                }
            }

            // Procesar cada sesión
            foreach ($inscriptionsBySession as $sessionId => $inscriptionsData) {
                $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->findOrFail($sessionId);

                if ($session->is_numbered) {
                    // 🔧 SESIÓN NUMERADA: Usar reserveSlot para cada slot
                    foreach ($inscriptionsData as $data) {
                        if (!$data['slot_id']) {
                            throw new \Exception("Slot ID requerido para sesión numerada {$sessionId}");
                        }

                        $slot = \App\Models\Slot::findOrFail($data['slot_id']);

                        $inscriptionService->reserveSlot(
                            $session,
                            $slot,
                            $cart,
                            $data['rate_id'],
                            [],
                            true
                        );
                    }
                } else {
                    // 🔧 SESIÓN NO NUMERADA: Crear inscripciones directamente
                    foreach ($inscriptionsData as $data) {
                        $rateId = $data['rate_id'];

                        // Obtener precio de la tarifa
                        $assignatedRate = $session->allRates()
                            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
                            ->where('rate_id', $rateId)
                            ->first();

                        if (!$assignatedRate) {
                            throw new \Exception("Tarifa {$rateId} no válida para sesión {$sessionId}");
                        }

                        // Crear inscripción sin slot
                        Inscription::create([
                            'session_id' => $session->id,
                            'cart_id' => $cart->id,
                            'rate_id' => $rateId,
                            'brand_id' => $session->brand_id,
                            'price' => $assignatedRate->price,
                            'price_sold' => $assignatedRate->price,
                            'barcode' => $this->generateUniqueBarcode(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error creating inscriptions in ticket office', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 🔧 FIX: Reescrito para usar CartService correctamente
     */
    private function createPacks(TicketOfficeRequest $request, Cart $cart): void
    {
        if (!$request->has('packs') || empty($request->input('packs'))) {
            return;
        }

        try {
            $groupPacks = [];

            foreach ($request->input('packs', []) as $rawPack) {
                if (!$rawPack) {
                    continue;
                }

                $pack = json_decode($rawPack);
                $packId = $pack->pack_id;

                if (!isset($groupPacks[$packId])) {
                    $groupPacks[$packId] = [
                        'pack_multiplier' => 0,
                        'selection' => []
                    ];
                }

                $groupPacks[$packId]['pack_multiplier']++;

                foreach ($pack->selection as $s) {
                    $sessionId = $s->session_id;

                    if (!isset($groupPacks[$packId]['selection'][$sessionId])) {
                        $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
                            ->find($sessionId);

                        $groupPacks[$packId]['selection'][$sessionId] = [
                            'is_numbered' => $session ? $session->is_numbered : false,
                            'slots' => []
                        ];
                    }

                    if ($s->slot_id != null) {
                        $groupPacks[$packId]['selection'][$sessionId]['slots'][] = $s->slot_id;
                    }
                }
            }

            // ✅ VALIDAR LÍMITES POR USUARIO EN PACKS
        $clientEmail = $cart->client ? $cart->client->email : $request->input('client.email');
        
        if ($clientEmail) {
            foreach ($groupPacks as $packId => $packData) {
                foreach ($packData['selection'] as $sessionId => $sessionData) {
                    $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
                        ->findOrFail($sessionId);

                    if ($session->limit_per_user) {
                        // Contar inscripciones ya compradas (INCLUYENDO PACKS)
                        $inscriptionsBuyed = Inscription::withoutGlobalScope(\App\Scopes\BrandScope::class)
                            ->where('session_id', $session->id)
                            ->whereHas('cart', function($q) use ($clientEmail) {
                                $q->whereHas('client', function($clientQ) use ($clientEmail) {
                                    $clientQ->where('email', $clientEmail);
                                })
                                ->whereHas('payments', function($paymentQ) {
                                    $paymentQ->whereNotNull('paid_at');
                                });
                            })
                            ->count();

                        // Calcular cuántas va a añadir con este pack
                        $cantidadAñadir = $packData['pack_multiplier']; // número de veces que compra el pack
                        $totalFinal = $inscriptionsBuyed + $cantidadAñadir;

                        \Log::info('🔍 Validación límite en pack', [
                            'session_id' => $session->id,
                            'pack_id' => $packId,
                            'email' => $clientEmail,
                            'inscriptions_buyed' => $inscriptionsBuyed,
                            'pack_multiplier' => $cantidadAñadir,
                            'total_final' => $totalFinal,
                            'max_allowed' => $session->max_per_user
                        ]);

                        if ($totalFinal > $session->max_per_user) {
                            $disponibles = $session->max_per_user - $inscriptionsBuyed;
                            
                            $sessionName = $session->event->name ?? '';
                            if ($session->name) {
                                $sessionName .= ' - ' . $session->name;
                            }
                            $sessionName .= ' (' . $session->starts_on->format('d/m/Y H:i') . ')';
                            
                            throw new \App\Exceptions\ApiException(
                                __('ticket-office.errors.limit_per_user_exceeded', [
                                    'session' => $sessionName,
                                    'buyed' => $inscriptionsBuyed,
                                    'max' => $session->max_per_user,
                                    'available' => $disponibles
                                ])
                            );
                        }
                    }
                }
            }
        }

            // Crear cada pack usando CartService
            $cartService = new \App\Services\Api\CartService();

            foreach ($groupPacks as $packId => $packData) {
                $selection = [];

                foreach ($packData['selection'] as $sessionId => $sessionData) {
                    $selection[] = [
                        'session_id' => $sessionId,
                        'is_numbered' => $sessionData['is_numbered'],
                        'slots' => $sessionData['slots']
                    ];
                }

                $packRequest = new Request([
                    'pack_id' => $packId,
                    'pack_multiplier' => $packData['pack_multiplier'],
                    'selection' => $selection
                ]);

                $cartService->setPack($cart, $packRequest);
            }
        } catch (\Exception $e) {
            \Log::error('Error creating packs in ticket office', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 🔧 FIX: Simplificado para crear gift cards
     */
    private function createGiftCarts(TicketOfficeRequest $request, Cart $cart): void
    {
        if (!$request->has('gift_cards.session_id') || empty($request->input('gift_cards.session_id'))) {
            return;
        }

        $inscriptionService = new InscriptionService();

        try {
            foreach ($request->input('gift_cards.session_id', []) as $index => $sessionId) {
                if (!$sessionId) {
                    continue;
                }

                $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->findOrFail($sessionId);

                $code = $request->input("gift_cards.code")[$index];
                $slotId = $request->input("gift_cards.slot_id")[$index] ?? null;

                // Verificar que la gift card existe y es válida
                $giftCard = \App\Models\GiftCard::where('code', $code)
                    ->where('event_id', $session->event_id)
                    ->whereDoesntHave('inscription')
                    ->firstOrFail();

                $rateId = $session->generalRate?->rate_id;

                if (!$rateId) {
                    throw new \Exception("Sesión {$sessionId} no tiene tarifa general configurada");
                }

                if ($session->is_numbered && $slotId) {
                    // Sesión numerada
                    $slot = \App\Models\Slot::findOrFail($slotId);

                    $inscription = $inscriptionService->reserveSlot(
                        $session,
                        $slot,
                        $cart,
                        $rateId,
                        ['code' => $code],
                        true
                    );
                } else {
                    // Sesión no numerada
                    $assignatedRate = $session->allRates()
                        ->withoutGlobalScope(\App\Scopes\BrandScope::class)
                        ->where('rate_id', $rateId)
                        ->first();

                    $inscription = Inscription::create([
                        'session_id' => $session->id,
                        'cart_id' => $cart->id,
                        'rate_id' => $rateId,
                        'brand_id' => $session->brand_id,
                        'code' => $code,
                        'price' => $assignatedRate->price ?? 0,
                        'price_sold' => $assignatedRate->price ?? 0,
                        'barcode' => $this->generateUniqueBarcode(),
                    ]);
                }

                // Asociar gift card a inscripción
                $giftCard->inscription()->associate($inscription);
                $giftCard->save();
            }
        } catch (\Exception $e) {
            \Log::error('Error creating gift cards in ticket office', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
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

    /**
     * 🔧 NUEVO: Generar barcode único
     */
    private function generateUniqueBarcode(): string
    {
        do {
            $barcode = strtoupper(bin2hex(random_bytes(6)));
        } while (Inscription::where('barcode', $barcode)->exists());

        return $barcode;
    }
}
