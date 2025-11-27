<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InscriptionService;
use App\Models\Session;
use App\Models\Slot;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Brand;
use App\Models\User;
use App\Models\Rate;
use App\Models\Inscription;
use App\Models\SessionSlot;
use App\Models\SessionTempSlot;
use App\Exceptions\SlotNotAvailableException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Test para verificar que no hay race conditions en inscripciones
 * NO usa RefreshDatabase - seguro para tu BD
 */
class InscriptionRaceConditionTest extends TestCase
{
    private InscriptionService $service;
    private ?Session $session = null;
    private ?Cart $cart1 = null;
    private ?Cart $cart2 = null;
    private ?User $seller = null;
    private array $createdCarts = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Desactivar el observer de Inscription para los tests
        Inscription::unsetEventDispatcher();

        $this->service = new InscriptionService();

        // Usar transacción para rollback después
        DB::beginTransaction();

        // Limpiar cache
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Limpiar cualquier dato temporal que pueda haber quedado
        if ($this->session) {
            // Limpiar inscripciones temporales
            SessionTempSlot::where('session_id', $this->session->id)
                ->whereIn('cart_id', $this->createdCarts)
                ->forceDelete();

            // Limpiar inscripciones
            Inscription::where('session_id', $this->session->id)
                ->whereIn('cart_id', $this->createdCarts)
                ->forceDelete();

            // Limpiar session slots creados en tests
            SessionSlot::where('session_id', $this->session->id)
                ->where('comment', 'LIKE', '%Test%')
                ->delete();
        }

        // Rollback para no afectar la BD
        DB::rollBack();

        Cache::flush();

        parent::tearDown();
    }

    /**
     * Test: Un slot no puede ser reservado dos veces
     */
    public function test_slot_cannot_be_reserved_twice()
    {
        $this->setupTestData();

        if (!$this->session) {
            $this->markTestSkipped('No hay sesiones numeradas disponibles');
        }

        $slot = $this->getAvailableSlot();

        if (!$slot) {
            $this->markTestSkipped('No hay slots disponibles');
        }

        // Primera reserva - debe funcionar
        $inscription1 = $this->service->reserveSlot(
            $this->session,
            $slot,
            $this->cart1,
            1
        );

        $this->assertInstanceOf(Inscription::class, $inscription1);

        // Segunda reserva - debe fallar
        $this->expectException(SlotNotAvailableException::class);

        $this->service->reserveSlot(
            $this->session,
            $slot,
            $this->cart2,
            1
        );
    }

    /**
     * Test: Reservas múltiples son atómicas (todo o nada)
     */
    public function test_multiple_slots_reservation_is_atomic()
    {
        $this->setupTestData();

        if (!$this->session) {
            $this->markTestSkipped('No hay sesiones numeradas disponibles');
        }

        // Obtener slots libres
        $availableSlots = $this->getAvailableSlots(3);

        if (count($availableSlots) < 3) {
            $this->markTestSkipped('No hay suficientes slots disponibles');
        }

        $slotIds = array_column($availableSlots, 'id');

        // Reservar el slot del medio con otro carrito
        $middleSlotId = $slotIds[1];
        $this->service->reserveSlot(
            $this->session,
            Slot::find($middleSlotId),
            $this->cart1,
            1
        );

        // Intentar reservar los 3 slots (debería fallar porque uno está ocupado)
        try {
            $this->service->reserveMultipleSlots(
                $this->session,
                $slotIds,
                $this->cart2,
                1
            );

            $this->fail('Debería haber lanzado excepción');
        } catch (SlotNotAvailableException $e) {
            // Esperado
            $this->assertStringContainsString('no están disponibles', $e->getMessage());
        }

        // Verificar que NINGUNO se reservó (transacción atómica)
        $inscriptions = Inscription::where('cart_id', $this->cart2->id)->count();
        $this->assertEquals(0, $inscriptions, 'No debería haber inscripciones del cart2');
    }

    /**
     * Test: Liberación de slot funciona correctamente
     */
    public function test_slot_release_works_correctly()
    {
        $this->setupTestData();

        if (!$this->session) {
            $this->markTestSkipped('No hay sesiones numeradas disponibles');
        }

        $slot = $this->getAvailableSlot();

        if (!$slot) {
            $this->markTestSkipped('No hay slots disponibles');
        }

        // Reservar slot
        $inscription = $this->service->reserveSlot(
            $this->session,
            $slot,
            $this->cart1,
            1
        );

        // Verificar que está reservado
        try {
            $this->service->reserveSlot(
                $this->session,
                $slot,
                $this->cart2,
                1
            );
            $this->fail('Debería haber lanzado excepción ya que el slot está reservado');
        } catch (SlotNotAvailableException $e) {
            // Esperado
            $this->assertTrue(true);
        }

        // Liberar slot
        $released = $this->service->releaseSlot($inscription);
        $this->assertTrue($released);

        // Ahora debería poder reservarse de nuevo
        $inscription2 = $this->service->reserveSlot(
            $this->session,
            $slot,
            $this->cart2,
            1
        );

        $this->assertInstanceOf(Inscription::class, $inscription2);
    }

    /**
     * Test: Bloqueos administrativos se respetan
     */
    public function test_administrative_blocks_are_respected()
    {
        $this->setupTestData();

        if (!$this->session) {
            $this->markTestSkipped('No hay sesiones numeradas disponibles');
        }

        $slot = $this->getAvailableSlot();

        if (!$slot) {
            $this->markTestSkipped('No hay slots disponibles');
        }

        // Bloquear administrativamente usando DB directamente para evitar observers
        DB::table('session_slot')->insert([
            'session_id' => $this->session->id,
            'slot_id' => $slot->id,
            'status_id' => 3, // Bloqueado
            'comment' => 'Test block - ' . uniqid()
        ]);

        // Intentar reservar - debe fallar
        $this->expectException(SlotNotAvailableException::class);

        $this->service->reserveSlot(
            $this->session,
            $slot,
            $this->cart1,
            1
        );
    }

    /**
     * Test: Simular múltiples peticiones concurrentes
     */
    public function test_concurrent_reservations_handle_correctly()
    {
        $this->setupTestData();

        if (!$this->session) {
            $this->markTestSkipped('No hay sesiones numeradas disponibles');
        }

        $slot = $this->getAvailableSlot();

        if (!$slot) {
            $this->markTestSkipped('No hay slots disponibles');
        }

        $results = [];
        $exceptions = [];

        // Simular 5 peticiones "concurrentes"
        for ($i = 0; $i < 5; $i++) {
            // Crear un carrito único para cada intento
            $cart = $this->createCart($this->cart1->client_id);

            try {
                $inscription = $this->service->reserveSlot(
                    $this->session,
                    $slot,
                    $cart,
                    1
                );
                $results[] = $inscription;
            } catch (SlotNotAvailableException $e) {
                $exceptions[] = $e;
            }
        }

        // Solo una reserva debería tener éxito
        $this->assertCount(1, $results, 'Solo una reserva debería tener éxito');
        $this->assertCount(4, $exceptions, 'Cuatro reservas deberían fallar');
    }

    /**
     * Obtener un slot disponible para la sesión
     */
    private function getAvailableSlot(): ?Slot
    {
        // Buscar slots que NO tengan inscripciones ni bloqueos
        $query = "
            SELECT s.* 
            FROM slots s
            WHERE s.space_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM inscriptions i 
                WHERE i.slot_id = s.id 
                AND i.session_id = ?
                AND i.deleted_at IS NULL
            )
            AND NOT EXISTS (
                SELECT 1 FROM session_slot ss 
                WHERE ss.slot_id = s.id 
                AND ss.session_id = ?
                AND ss.status_id IN (2,3,4,5,7,8)
            )
            AND NOT EXISTS (
                SELECT 1 FROM session_temp_slot sts 
                WHERE sts.slot_id = s.id 
                AND sts.session_id = ?
                AND sts.deleted_at IS NULL
                AND (sts.expires_on IS NULL OR sts.expires_on > NOW())
            )
            LIMIT 1
        ";

        $result = DB::select($query, [
            $this->session->space_id,
            $this->session->id,
            $this->session->id,
            $this->session->id
        ]);

        return empty($result) ? null : Slot::find($result[0]->id);
    }

    /**
     * Obtener múltiples slots disponibles
     */
    private function getAvailableSlots(int $count): array
    {
        $query = "
            SELECT s.* 
            FROM slots s
            WHERE s.space_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM inscriptions i 
                WHERE i.slot_id = s.id 
                AND i.session_id = ?
                AND i.deleted_at IS NULL
            )
            AND NOT EXISTS (
                SELECT 1 FROM session_slot ss 
                WHERE ss.slot_id = s.id 
                AND ss.session_id = ?
                AND ss.status_id IN (2,3,4,5,7,8)
            )
            AND NOT EXISTS (
                SELECT 1 FROM session_temp_slot sts 
                WHERE sts.slot_id = s.id 
                AND sts.session_id = ?
                AND sts.deleted_at IS NULL
                AND (sts.expires_on IS NULL OR sts.expires_on > NOW())
            )
            LIMIT ?
        ";

        return DB::select($query, [
            $this->session->space_id,
            $this->session->id,
            $this->session->id,
            $this->session->id,
            $count
        ]);
    }

    /**
     * Método helper para crear un carrito con todos los campos necesarios
     */
    private function createCart($clientId): Cart
    {
        $cartId = DB::table('carts')->insertGetId([
            'brand_id' => $this->session->brand_id,
            'client_id' => $clientId,
            'expires_on' => Carbon::now()->addMinutes(15),
            'seller_id' => $this->seller->id,
            'seller_type' => User::class,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->createdCarts[] = $cartId;

        return Cart::find($cartId);
    }

    /**
     * Configurar datos de prueba
     */
    private function setupTestData(): void
    {
        // Buscar una sesión numerada con espacio
        $this->session = Session::where('is_numbered', true)
            ->whereNotNull('space_id')
            ->whereHas('space.slots')
            ->first();

        if (!$this->session) {
            return;
        }

        // Obtener la brand de la sesión
        $brand = Brand::find($this->session->brand_id);

        if (!$brand) {
            $this->markTestSkipped('No se encontró la brand de la sesión');
            return;
        }

        // Buscar un usuario que pertenezca a esta brand a través de la tabla pivote
        $this->seller = $brand->users()->first();

        if (!$this->seller) {
            // Si no hay usuarios asociados a esta brand, crear uno temporal
            $this->seller = User::create([
                'name' => 'Test Seller',
                'email' => 'testseller_' . uniqid() . '@test.com',
                'password' => bcrypt('password')
            ]);

            // Asociar el usuario con la brand
            $this->seller->brands()->attach($brand->id);
        }

        // Crear cliente de prueba con TODOS los campos requeridos
        $client = Client::firstOrCreate(
            [
                'email' => 'test_' . uniqid() . '@example.com',
                'brand_id' => $this->session->brand_id
            ],
            [
                'name' => 'Test',
                'surname' => 'Client',
                'phone' => '123456789',
                'brand_id' => $this->session->brand_id,
                'nif' => '12345678A',
                'dni' => '12345678A'
            ]
        );

        // Crear carritos usando el helper que bypasses fillable
        $this->cart1 = $this->createCart($client->id);
        $this->cart2 = $this->createCart($client->id);
    }
}
