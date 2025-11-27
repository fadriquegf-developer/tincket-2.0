<?php

namespace App\Services\Api;

use App\Models\Cart;
use App\Models\Pack;
use App\Models\Client;
use App\Models\Session;
use App\Models\Slot;
use App\Models\GroupPack;
use App\Models\Inscription;
use App\Services\Api\GiftCardService;
use App\Services\InscriptionService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartService extends AbstractService
{
    private InscriptionService $inscriptionService;

    public function __construct()
    {
        $this->inscriptionService = new InscriptionService();
    }

    /**
     * Crear un nuevo carrito
     */
    public function createCart(Request $request): Cart
    {
        $cart = DB::transaction(function () use ($request) {
            // Obtener usuario autenticado de forma segura
            $user = auth()->user() ?? auth('backpack')->user() ?? $request->attributes->get('user');

            if (!$user) {
                throw new \Exception('Usuario no autenticado. No se puede crear el carrito.');
            }

            // 🔧 FIX: Obtener el brand correctamente
            // Prioridad: 1) Request parameter/attribute (para client-front), 2) Helper global (para backend)
            $brand = $request->get('brand')
                ?? $request->attributes->get('brand')
                ?? get_current_brand();

            if (!$brand) {
                throw new \Exception('No se pudo determinar el brand actual. Verifica la configuración de brand en el request o middleware.');
            }

            // 🔧 FIX: Obtener el TTL del carrito de forma segura
            $cartTTL = $brand->getSetting(
                \App\Models\Brand::EXTRA_CONFIG['CART_TTL_KEY'],
                Cart::DEFAULT_MINUTES_TO_EXPIRE
            );

            $cart = new Cart();
            $cart->token = Str::uuid()->toString();
            $cart->brand()->associate($brand);
            $cart->seller()->associate($user);
            $cart->expires_on = \Carbon\Carbon::now()->addMinutes($cartTTL);
            $cart->save();

            return $cart;
        });

        return $cart->fresh();
    }

    /**
     * Agregar inscripciones al carrito (sesiones sin numeración)
     */
    public function setInscriptions(Cart $cart, Request $request)
    {
        // ✅ DESHABILITAR BrandScope para permitir sesiones de promotores hijos
        $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->findOrFail($request->get('session_id'));

        $metadata = $request->get('metadata');

        DB::transaction(function () use ($cart, $session, $request, $metadata) {
            // ✅ Cargar la relación 'session' en las inscripciones antiguas
            $oldInscriptions = $cart->inscriptions()
                ->where('session_id', $session->id)
                ->whereNull('group_pack_id')
                ->with([
                    'session' => function ($q) {
                        $q->withoutGlobalScope(\App\Scopes\BrandScope::class);
                    }
                ])
                ->get();

            foreach ($oldInscriptions as $inscription) {
                $this->inscriptionService->releaseSlot($inscription);
            }

            // Crear nuevas inscripciones
            $inscriptions = $request->get('inscriptions', []);

            foreach ($inscriptions as $inscriptionData) {
                $quantity = $inscriptionData['quantity'] ?? 1;
                $rateId = $inscriptionData['rate_id'];

                for ($i = 0; $i < $quantity; $i++) {
                    // ✨ AÑADIR: Obtener metadata para esta inscripción
                    $inscriptionMetadata = null;
                    if ($metadata && isset($metadata[$rateId][$i])) {
                        $inscriptionMetadata = json_encode($metadata[$rateId][$i]);
                    }

                    Inscription::create([
                        'session_id' => $session->id,
                        'cart_id' => $cart->id,
                        'rate_id' => $rateId,
                        'brand_id' => $session->brand_id,
                        'code' => $inscriptionData['codes'][$i] ?? null,
                        'metadata' => $inscriptionMetadata, // ✅ AÑADIR AQUÍ
                        'price' => $this->getRatePrice($session, $rateId),
                        'price_sold' => $this->getRatePrice($session, $rateId),
                        'barcode' => $this->generateUniqueBarcode()
                    ]);
                }
            }
        }, 3);

        return $cart->fresh()->load('inscriptions');
    }

    /**
     * Agregar slots específicos (sesiones numeradas)
     */
    public function setSlots(Cart $cart, Request $request)
    {
        $sessionId = $request->get('session_id');
        $metadata = $request->get('metadata');

        // ✅ DESHABILITAR BrandScope para permitir sesiones de promotores hijos
        $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->findOrFail($sessionId);

        // Preparar slots para reservar
        $positions = $request->get('inscriptions', []);
        if (empty($positions) && $request->has('position')) {
            $positions = [$request->get('position')];
        }

        DB::transaction(function () use ($cart, $session, $positions, $metadata) {
            foreach ($positions as $position) {
                if (!isset($position['slot_id'])) {
                    continue;
                }

                $slot = Slot::findOrFail($position['slot_id']);
                $rateId = $position['rate_id'] ?? $this->getDefaultRateId($session);

                $inscriptionMetadata = null;
                if ($metadata && isset($metadata[$rateId][$position['slot_id']])) {
                    $inscriptionMetadata = $metadata[$rateId][$position['slot_id']];
                }

                // Usar el nuevo servicio con protección contra race conditions
                $this->inscriptionService->reserveSlot(
                    $session,
                    $slot,
                    $cart,
                    $rateId,
                    [
                        'code' => $position['code'] ?? null,
                        'metadata' => $inscriptionMetadata
                    ]
                );
            }
        }, 3);

        return $cart->fresh()->load('inscriptions.slot');
    }

    /**
     * Crear packs con inscripciones
     */
    public function setPack(Cart $cart, Request $request)
    {
        DB::transaction(function () use ($cart, $request) {
            // ✅ DESHABILITAR BrandScope para permitir packs de promotores hijos
            $pack = Pack::withoutGlobalScope(\App\Scopes\BrandScope::class)
                ->findOrFail($request->get('pack_id'));

            $packMultiplier = min(
                (int) $request->get('pack_multiplier', 1),
                $pack->max_per_cart
            );

            // Preparar slots organizados por sesión
            $slotsBySession = [];
            foreach ($request->get('selection', []) as $selection) {
                $sessionId = $selection['session_id'];
                $isNumbered = $selection['is_numbered'] ?? false;

                if ($isNumbered && isset($selection['slots'])) {
                    // Slots numerados
                    $slotsBySession[$sessionId] = [
                        'is_numbered' => true,
                        'slots' => $selection['slots']
                    ];
                } else {
                    // No numerados
                    $slotsBySession[$sessionId] = [
                        'is_numbered' => false,
                        'quantity' => $selection['quantity'] ?? 1,
                        'codes' => $selection['codes'] ?? []
                    ];
                }
            }

            // Crear cada pack
            for ($packIndex = 0; $packIndex < $packMultiplier; $packIndex++) {
                $packInscriptionIds = [];

                $groupPack = GroupPack::create([
                    'cart_id' => $cart->id,
                    'pack_id' => $pack->id,
                    'brand_id' => $cart->brand_id
                ]);

                // Procesar cada sesión para este pack
                foreach ($slotsBySession as $sessionId => $sessionData) {
                    // ✅ DESHABILITAR BrandScope al buscar cada sesión del pack
                    $session = Session::withoutGlobalScope(\App\Scopes\BrandScope::class)
                        ->findOrFail($sessionId);

                    if ($sessionData['is_numbered']) {
                        // Sesión numerada: tomar 1 slot por pack
                        if (isset($sessionData['slots'][$packIndex])) {
                            $slotData = $sessionData['slots'][$packIndex];
                            $slotId = is_array($slotData) ? ($slotData['id'] ?? null) : $slotData;

                            if (!$slotId) {
                                continue;
                            }

                            $slot = Slot::find($slotId);
                            if (!$slot) {
                                continue;
                            }

                            $inscription = $this->inscriptionService->reserveSlot(
                                $session,
                                $slot,
                                $cart,
                                $this->getDefaultRateId($session),
                                ['code' => $slotData['code'] ?? null]
                            );

                            // Asociar al pack
                            $inscription->group_pack_id = $groupPack->id;
                            $inscription->save();
                            $packInscriptionIds[] = $inscription->id;
                        } else {
                            \Log::warning('No hay slot disponible para este pack', [
                                'pack_index' => $packIndex,
                                'session_id' => $sessionId,
                                'available_slots' => count($sessionData['slots'])
                            ]);
                        }
                    } else {
                        // Sesión no numerada: crear 1 inscripción por pack
                        $codes = $sessionData['codes'] ?? [];
                        $code = isset($codes[$packIndex]) ? $codes[$packIndex] : null;
                        $rateId = $this->getDefaultRateId($session);

                        $newInscription = Inscription::create([
                            'session_id' => $session->id,
                            'cart_id' => $cart->id,
                            'group_pack_id' => $groupPack->id,
                            'rate_id' => $rateId,
                            'brand_id' => $session->brand_id,
                            'code' => $code,
                            'price' => $this->getRatePrice($session, $rateId),
                            'price_sold' => $this->getRatePrice($session, $rateId),
                            'barcode' => $this->generateUniqueBarcode()
                        ]);
                        $packInscriptionIds[] = $newInscription->id;
                    }
                }

                // Aplicar precio del pack
                $this->applyPackPricing($groupPack, $pack, $packInscriptionIds);
            }
        }, 3);

        return $cart->fresh()->load('groupPacks.inscriptions');
    }

    /**
     * Agregar gift cards
     */
    public function setGiftCard(Cart $cart, Request $request)
    {
        $giftService = app(GiftCardService::class);
        $giftService->setCart($cart)->createGifts($request->all());
        return true;
    }

    /**
     * Asignar cliente al carrito
     */
    public function setClient(Cart $cart, Request $request)
    {
        $cart->checkBrandOwnership();

        $clientId = $request->get('client_id');
        $client = Client::ownedByBrand()
            ->where('id', $clientId)
            ->where('email', $request->get('email'))
            ->first();

        if (!$client) {
            throw new \App\Exceptions\ApiException(
                "Cliente inválido (ID: $clientId) para asociar con Cart ID $cart->id"
            );
        }

        $cart->client()->associate($client)->save();
        return $cart->fresh();
    }

    /**
     * Obtener precio de una tarifa para una sesión
     * ✅ DESHABILITAR BrandScope en AssignatedRate y Rate
     */
    private function getRatePrice(Session $session, int $rateId): float
    {
        $assignatedRate = $session->allRates()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->with([
                'rate' => function ($q) {
                    $q->withoutGlobalScope(\App\Scopes\BrandScope::class);
                }
            ])
            ->where('rate_id', $rateId)
            ->first();

        return $assignatedRate ? $assignatedRate->price : 0.00;
    }

    /**
     * Obtener la tarifa por defecto de una sesión
     * Usa la misma lógica que PackApiController para consistencia
     * ✅ DESHABILITAR BrandScope en consultas
     */
    private function getDefaultRateId(Session $session): int
    {
        $generalRate = $session->generalRate ?? $session->getAttribute('general_rate');

        if ($generalRate && isset($generalRate->rate_id)) {
            return $generalRate->rate_id;
        }

        // ✅ Buscar tarifas WEB válidas (públicas o privadas con fechas válidas)
        $assignatedRates = \DB::table('assignated_rates')
            ->where('session_id', $session->id)
            ->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhere(function ($sub) {
                        $sub->where('is_private', true)
                            ->where(function ($dates) {
                                $dates->where(function ($since) {
                                    $since->whereNull('available_since')
                                        ->orWhere('available_since', '<=', now());
                                })
                                    ->where(function ($until) {
                                        $until->whereNull('available_until')
                                            ->orWhere('available_until', '>=', now());
                                    });
                            });
                    });
            })
            ->get();

        if ($assignatedRates->isEmpty()) {
            throw new \Exception("La sesión {$session->id} no tiene tarifas web válidas configuradas");
        }

        $selectedRate = $assignatedRates->firstWhere('is_private', true)
            ?: $assignatedRates->sortByDesc('price')->first();

        if (!$selectedRate) {
            throw new \Exception("No se pudo seleccionar tarifa para sesión {$session->id}");
        }

        return $selectedRate->rate_id;
    }

    /**
     * Aplicar precio del pack al grupo
     */
    private function applyPackPricing(GroupPack $groupPack, Pack $pack, array $inscriptionIds): void
    {
        $inscriptions = Inscription::whereIn('id', $inscriptionIds)->get();
        $totalInscriptions = $inscriptions->count();

        if ($totalInscriptions === 0) {
            return;
        }

        $totalOriginalPrice = $inscriptions->sum('price');
        $uniqueSessionsCount = $inscriptions->pluck('session_id')->unique()->count();

        $applicableRule = $pack->rules()
            ->where(function ($q) use ($uniqueSessionsCount) {
                $q->where('number_sessions', $uniqueSessionsCount)
                    ->orWhere('all_sessions', true);
            })
            ->orderBy('all_sessions', 'desc')
            ->orderBy('number_sessions', 'asc')
            ->first();

        if (!$applicableRule) {
            return;
        }

        $finalPackPrice = 0;

        if ($applicableRule->percent_pack) {
            $discount = $applicableRule->percent_pack / 100;
            $finalPackPrice = $totalOriginalPrice * (1 - $discount);
        } elseif ($applicableRule->price_pack) {
            $finalPackPrice = $applicableRule->price_pack;
        } else {
            return;
        }

        $pricePerInscription = $finalPackPrice / $totalInscriptions;

        foreach ($inscriptions as $inscription) {
            \DB::table('inscriptions')
                ->where('id', $inscription->id)
                ->update([
                    'price' => $pricePerInscription,
                    'price_sold' => $pricePerInscription,
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * 🔧 FIX: Añadido método para generar barcode único
     */
    private function generateUniqueBarcode(): string
    {
        do {
            $barcode = strtoupper(bin2hex(random_bytes(6)));
        } while (Inscription::where('barcode', $barcode)->exists());

        return $barcode;
    }
}
