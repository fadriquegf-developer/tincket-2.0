<?php

namespace Tests\Commands;

use App\Models\Cart;
use App\Models\Session;
use App\Services\RedisSlotsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Comando para verificar la consistencia entre la base de datos y Redis.
 * Específicamente prueba que los slots se liberan correctamente al eliminar carritos.
 * 
 * USO:
 * - php artisan test:redis-consistency --cart=123     (Prueba eliminación de carrito)
 * - php artisan test:redis-consistency --session=456  (Verifica cache de sesión)
 * - php artisan test:redis-consistency --create-test  (Crea carrito de prueba)
 * 
 * CUÁNDO USAR:
 * - Si sospechas que hay slots bloqueados incorrectamente
 * - Para verificar que los Observers funcionan correctamente
 * - Después de cambios en CartObserver o InscriptionObserver
 * - Si hay discrepancias entre slots disponibles en BD vs Redis
 * 
 * ADVERTENCIA: El flag --cart ELIMINA el carrito especificado. Usar con precaución.
 */
class TestRedisConsistency extends Command
{
    protected $signature = 'test:redis-consistency {--cart=} {--session=} {--create-test}';
    protected $description = 'Test Redis cache consistency';

    public function handle()
    {
        $this->info('Testing Redis consistency...');

        // Opción para crear un carrito de prueba
        if ($this->option('create-test')) {
            $this->createTestCart();
            return;
        }

        // Test 1: Cart deletion
        if ($cartId = $this->option('cart')) {
            $this->testCartDeletion($cartId);
        }

        // Test 2: Session cache
        if ($sessionId = $this->option('session')) {
            $this->testSessionCache($sessionId);
        }

        if (!$this->option('cart') && !$this->option('session')) {
            $this->warn('No options provided. Use --cart=ID or --session=ID or --create-test');
            $this->info('');
            $this->info('Finding recent test candidates...');

            // Buscar un carrito con inscripciones no eliminado
            $cart = Cart::has('inscriptions')
                ->whereNotNull('confirmation_code')
                ->latest()
                ->first();

            if ($cart) {
                $this->info("Found cart {$cart->id} with {$cart->inscriptions->count()} inscriptions");
                $this->info("Run: php artisan test:redis-consistency --cart={$cart->id}");
            }

            // Buscar una sesión numerada
            $session = Session::where('is_numbered', true)
                ->where('ends_on', '>', now())
                ->latest()
                ->first();

            if ($session) {
                $this->info("Found session {$session->id} ({$session->name})");
                $this->info("Run: php artisan test:redis-consistency --session={$session->id}");
            }
        }

        $this->info('Tests completed!');
    }

    private function testCartDeletion($cartId)
    {
        $this->info("Testing cart deletion for Cart ID: {$cartId}");

        // Verificar si el carrito existe
        $cart = Cart::withTrashed()->find($cartId);

        if (!$cart) {
            $this->error("Cart {$cartId} not found");
            return;
        }

        if ($cart->trashed()) {
            $this->warn("Cart {$cartId} is already deleted (soft deleted at {$cart->deleted_at})");

            // Verificar que los slots estén libres
            $this->info("Checking if slots were properly freed...");

            $inscriptions = DB::table('inscriptions')
                ->where('cart_id', $cartId)
                ->get();

            if ($inscriptions->isEmpty()) {
                $this->info("No inscriptions found for this cart");
                return;
            }

            foreach ($inscriptions as $inscription) {
                if ($inscription->slot_id && $inscription->session_id) {
                    $session = Session::find($inscription->session_id);
                    if ($session) {
                        $redis = new RedisSlotsService($session);
                        $isAvailable = $redis->isSlotAvailable($inscription->slot_id);

                        if ($isAvailable) {
                            $this->info("✅ Slot {$inscription->slot_id} is properly freed");
                        } else {
                            $this->error("❌ Slot {$inscription->slot_id} is still locked!");
                        }
                    }
                }
            }
            return;
        }

        // El carrito existe y no está eliminado
        $this->info("Cart found with {$cart->inscriptions->count()} inscriptions");

        if ($cart->inscriptions->isEmpty()) {
            $this->warn("Cart has no inscriptions to test");
            return;
        }

        // Verificar slots antes
        $slotsBefore = [];
        foreach ($cart->inscriptions as $inscription) {
            if ($inscription->slot_id && $inscription->session) {
                $redis = new RedisSlotsService($inscription->session);
                $isAvailable = $redis->isSlotAvailable($inscription->slot_id);
                $slotsBefore[$inscription->slot_id] = [
                    'available' => $isAvailable,
                    'session_id' => $inscription->session_id
                ];
                $this->info("Slot {$inscription->slot_id} before deletion: " . ($isAvailable ? '🟢 available' : '🔴 locked'));
            }
        }

        if (empty($slotsBefore)) {
            $this->warn("No slots with sessions found in inscriptions");
            return;
        }

        // Confirmar eliminación
        if (!$this->confirm("Do you want to DELETE cart {$cartId}? This will test the deletion process.")) {
            $this->info("Cancelled");
            return;
        }

        // Eliminar carrito
        $this->info("Deleting cart...");
        $cart->delete();

        // Esperar un momento para que los observers se ejecuten
        sleep(1);

        // Verificar slots después
        $this->info("\nChecking slots after deletion:");
        foreach ($slotsBefore as $slotId => $data) {
            $session = Session::find($data['session_id']);
            if ($session) {
                $redis = new RedisSlotsService($session);
                $isAvailable = $redis->isSlotAvailable($slotId);

                if ($isAvailable) {
                    $this->info("✅ Slot {$slotId} is now available (properly freed)");
                } else {
                    $this->error("❌ Slot {$slotId} is still locked (should be available!)");

                    // Debug adicional
                    $sessionSlot = DB::table('session_slot')
                        ->where('session_id', $data['session_id'])
                        ->where('slot_id', $slotId)
                        ->first();

                    if ($sessionSlot) {
                        $this->warn("  - SessionSlot still exists with status_id: {$sessionSlot->status_id}");
                    }

                    $tempSlot = DB::table('session_temp_slot')
                        ->where('session_id', $data['session_id'])
                        ->where('slot_id', $slotId)
                        ->first();

                    if ($tempSlot) {
                        $this->warn("  - TempSlot still exists, expires: {$tempSlot->expires_on}");
                    }
                }
            } else {
                $this->warn("Session {$data['session_id']} not found for slot {$slotId}");
            }
        }
    }

    private function createTestCart()
    {
        $this->info("Creating test cart with inscriptions...");

        // Buscar una sesión numerada disponible
        $session = Session::where('is_numbered', true)
            ->where('ends_on', '>', now()->addDay())
            ->whereHas('allRates')
            ->first();

        if (!$session) {
            $this->error("No suitable numbered session found");
            return;
        }

        $this->info("Using session: {$session->id} - {$session->name}");

        // Crear carrito de prueba
        $cart = Cart::create([
            'brand_id' => $session->brand_id,
            'expires_on' => now()->addMinutes(15),
            'token' => \Str::random(32),
            'confirmation_code' => 'TEST-' . time(),
            'client_id' => \App\Models\Client::first()->id ?? null,
            'seller_id' => backpack_user()->id,
            'seller_type' => 'App\Models\User'
        ]);

        $this->info("Created cart: {$cart->id}");

        // Buscar slots disponibles
        $redis = new RedisSlotsService($session);
        $availableSlots = [];

        $slots = \App\Models\Slot::where('space_id', $session->space_id)
            ->limit(10)
            ->get();

        foreach ($slots as $slot) {
            if ($redis->isSlotAvailable($slot->id)) {
                $availableSlots[] = $slot->id;
                if (count($availableSlots) >= 3) break;
            }
        }

        if (empty($availableSlots)) {
            $this->error("No available slots found");
            $cart->delete();
            return;
        }

        // Crear inscripciones
        $rate = $session->allRates->first();
        foreach ($availableSlots as $slotId) {
            \App\Models\Inscription::create([
                'session_id' => $session->id,
                'cart_id' => $cart->id,
                'slot_id' => $slotId,
                'rate_id' => $rate->rate_id,
                'brand_id' => $session->brand_id,
                'price' => $rate->price,
                'price_sold' => $rate->price,
                'barcode' => \Str::random(13)
            ]);
            $this->info("Created inscription for slot {$slotId}");
        }

        $this->info("\n✅ Test cart created successfully!");
        $this->info("Cart ID: {$cart->id}");
        $this->info("Inscriptions: " . count($availableSlots));
        $this->info("\nNow run: php artisan test:redis-consistency --cart={$cart->id}");
    }

    private function testSessionCache($sessionId)
    {
        $this->info("Testing session cache for Session ID: {$sessionId}");

        $session = Session::find($sessionId);
        if (!$session) {
            $this->error("Session not found");
            return;
        }

        $brandPrefix = $session->brand_id ? "b{$session->brand_id}" : 'default';

        // Verificar caches
        $caches = [
            "{$brandPrefix}:free:s{$sessionId}" => "Free positions",
            "{$brandPrefix}:available_web:s{$sessionId}" => "Available web",
            "{$brandPrefix}:blocked:s{$sessionId}" => "Blocked inscriptions",
            "session_{$sessionId}_public_rates_formatted" => "Public rates",
        ];

        foreach ($caches as $key => $name) {
            $exists = Cache::has($key);
            $this->info("{$name}: " . ($exists ? "✅ Cached" : "❌ Not cached"));
        }

        // Test invalidación
        $this->info("\nTesting cache invalidation...");

        // Cambiar algo
        $oldMaxPlaces = $session->max_places;
        $session->max_places = $oldMaxPlaces + 1;
        $session->save();

        // Verificar que se invalidó
        foreach ($caches as $key => $name) {
            $exists = Cache::has($key);
            if ($exists) {
                $this->warn("{$name}: Still cached after change (will regenerate)");
            }
        }

        // Restaurar
        $session->max_places = $oldMaxPlaces;
        $session->save();

        $this->info("✅ Session cache test completed");
    }
}
