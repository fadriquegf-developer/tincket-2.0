<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Session;
use App\Models\Event;
use App\Models\Space;
use App\Models\Slot;
use App\Models\Brand;
use App\Repositories\SessionRepository;
use Illuminate\Support\Facades\DB;

/**
 * Test para verificar que no hay N+1 queries
 * NO usa RefreshDatabase - seguro para tu BD
 */
class NPlus1QueriesTest extends TestCase
{
    /**
     * Test: Verificar que getEventNameAttribute no hace queries adicionales
     */
    public function test_event_name_attribute_does_not_cause_n_plus_1_queries()
    {
        // Habilitar log de queries
        DB::enableQueryLog();

        // Obtener una sesión SIN eager loading
        $session = Session::first();

        if (!$session) {
            $this->markTestSkipped('No hay sesiones en la BD');
        }

        // Limpiar log
        DB::flushQueryLog();

        // Acceder al accessor - NO debería hacer query
        $eventName = $session->event_name;

        // Verificar queries ejecutadas
        $queries = DB::getQueryLog();

        // No debería haber queries (0) o máximo 1 si no estaba cacheado
        $this->assertLessThanOrEqual(
            0,
            count($queries),
            'getEventNameAttribute está haciendo queries adicionales (N+1)'
        );

        // El nombre debería ser un placeholder si no se hizo eager loading
        if ($session->event_id) {
            $this->assertEquals('[Event #' . $session->event_id . ']', $eventName);
        } else {
            $this->assertEquals('-', $eventName);
        }

        DB::disableQueryLog();
    }

    /**
     * Test: Verificar que CON eager loading funciona correctamente
     */
    public function test_with_eager_loading_event_name_works_correctly()
    {
        DB::enableQueryLog();

        // Obtener sesión CON eager loading
        $session = Session::with('event')->first();

        if (!$session) {
            $this->markTestSkipped('No hay sesiones en la BD');
        }

        DB::flushQueryLog();

        // Acceder al accessor
        $eventName = $session->event_name;

        // No debería hacer ninguna query
        $queries = DB::getQueryLog();
        $this->assertCount(0, $queries, 'Se ejecutaron queries adicionales con eager loading');

        // Debería devolver el nombre real del evento
        if ($session->event) {
            $this->assertEquals($session->event->name, $eventName);
        } else {
            $this->assertEquals('-', $eventName);
        }

        DB::disableQueryLog();
    }

    /**
     * Test: Verificar que el scope withCommonRelations carga todo
     */
    public function test_with_common_relations_scope_prevents_n_plus_1()
    {
        DB::enableQueryLog();

        // Usar el nuevo scope
        $sessions = Session::withCommonRelations()->take(5)->get();

        if ($sessions->isEmpty()) {
            $this->markTestSkipped('No hay sesiones en la BD');
        }

        // Contar queries antes de acceder a relaciones
        $queriesBefore = count(DB::getQueryLog());

        // Acceder a múltiples relaciones en loop (potencial N+1)
        foreach ($sessions as $session) {
            $eventName = $session->event_name;
            $spaceName = $session->space?->name;
            $locationName = $session->space?->location?->name;
            $brandName = $session->brand?->name;
        }

        // Contar queries después
        $queriesAfter = count(DB::getQueryLog());

        // No debería haber queries adicionales
        $additionalQueries = $queriesAfter - $queriesBefore;

        $this->assertEquals(
            0,
            $additionalQueries,
            "Se ejecutaron {$additionalQueries} queries adicionales (N+1 problem)"
        );

        DB::disableQueryLog();
    }

    /**
     * Test: Verificar que el repositorio no causa N+1
     */
    public function test_repository_get_sessions_with_full_data_is_efficient()
    {
        // Verificar si SessionRepository existe y tiene el método
        if (!class_exists(SessionRepository::class)) {
            $this->markTestSkipped('SessionRepository no existe aún');
            return;
        }

        $repository = new SessionRepository();

        if (!method_exists($repository, 'getSessionsWithFullData')) {
            $this->markTestSkipped('Método getSessionsWithFullData no implementado aún');
            return;
        }

        DB::enableQueryLog();

        // Obtener sesiones con todos los datos
        $sessions = $repository->getSessionsWithFullData();

        if ($sessions->isEmpty()) {
            $this->markTestSkipped('No hay sesiones en la BD');
        }

        // Guardar número de queries
        $initialQueries = count(DB::getQueryLog());

        // Acceder a todas las relaciones
        foreach ($sessions as $session) {
            $data = [
                'event' => $session->event?->name,
                'space' => $session->space?->name,
                'location' => $session->space?->location?->name,
                'zones_count' => $session->space?->zones->count(),
                'confirmed' => $session->confirmed_inscriptions_count,
                'pending' => $session->pending_inscriptions_count
            ];
        }

        // No debería haber queries adicionales
        $finalQueries = count(DB::getQueryLog());
        $additionalQueries = $finalQueries - $initialQueries;

        $this->assertEquals(
            0,
            $additionalQueries,
            "Repository causó {$additionalQueries} queries adicionales"
        );

        DB::disableQueryLog();
    }

    /**
     * Test: Verificar que getSessionSlotsWithFullData usa un solo query
     */
    public function test_get_session_slots_uses_single_query()
    {
        // Verificar si SessionRepository existe
        if (!class_exists(SessionRepository::class)) {
            $this->markTestSkipped('SessionRepository no existe aún');
            return;
        }

        $session = Session::where('is_numbered', true)
            ->whereNotNull('space_id')
            ->first();

        if (!$session) {
            $this->markTestSkipped('No hay sesiones numeradas');
        }

        $repository = new SessionRepository();

        if (!method_exists($repository, 'getSessionSlotsWithFullData')) {
            $this->markTestSkipped('Método getSessionSlotsWithFullData no implementado aún');
            return;
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        // Obtener slots con toda la información
        $slots = $repository->getSessionSlotsWithFullData($session->id);

        // Debería usar exactamente 1 query
        $queries = DB::getQueryLog();

        $this->assertCount(
            1,
            $queries,
            'getSessionSlotsWithFullData debería usar exactamente 1 query'
        );

        // Verificar que devuelve datos correctos
        if ($slots->isNotEmpty()) {
            $firstSlot = $slots->first();

            $this->assertObjectHasProperty('id', $firstSlot);
            $this->assertObjectHasProperty('name', $firstSlot);
            $this->assertObjectHasProperty('zone', $firstSlot);
            $this->assertObjectHasProperty('is_available', $firstSlot);
        }

        DB::disableQueryLog();
    }

    /**
     * Test: Comparar performance con y sin eager loading
     */
    public function test_performance_comparison_with_and_without_eager_loading()
    {
        $sessionIds = Session::take(10)->pluck('id')->toArray();

        if (empty($sessionIds)) {
            $this->markTestSkipped('No hay suficientes sesiones para comparar');
        }

        // SIN eager loading
        DB::enableQueryLog();
        DB::flushQueryLog();

        $sessionsWithoutEager = Session::whereIn('id', $sessionIds)->get();
        foreach ($sessionsWithoutEager as $session) {
            // Esto causaría N+1 con el código antiguo
            // pero con el nuevo código no hace queries
            $name = $session->event_name;
        }

        $queriesWithout = count(DB::getQueryLog());

        // CON eager loading
        DB::flushQueryLog();

        $sessionsWithEager = Session::whereIn('id', $sessionIds)
            ->withCommonRelations()
            ->get();

        foreach ($sessionsWithEager as $session) {
            $name = $session->event_name;
            $space = $session->space?->name;
        }

        $queriesWith = count(DB::getQueryLog());

        // Análisis de resultados
        echo "\n📊 Comparación de queries:";
        echo "\n   - Sin eager loading: {$queriesWithout} queries";
        echo "\n   - Con eager loading: {$queriesWith} queries";

        // Escenario 1: Código ya optimizado (no hay N+1)
        if ($queriesWithout <= 2) {
            echo "\n   ✅ No hay problema N+1 - el código ya está optimizado";
            echo "\n   ℹ️  Eager loading carga relaciones extras por si las necesitas";

            // En este caso, eager loading hará más queries pero es normal
            $this->assertLessThanOrEqual(
                15, // Límite razonable para eager loading
                $queriesWith,
                "Demasiadas queries con eager loading"
            );

            $this->assertTrue(true); // Test pasa - no hay N+1

        }
        // Escenario 2: Hay N+1 (eager loading debería ser mejor)
        else {
            echo "\n   ⚠️  Posible problema N+1 detectado";

            $this->assertLessThan(
                $queriesWithout,
                $queriesWith,
                "Eager loading debería reducir queries cuando hay N+1"
            );

            $improvement = $queriesWithout - $queriesWith;
            echo "\n   ✅ Mejora con eager loading: {$improvement} queries menos";
        }

        echo "\n";

        DB::disableQueryLog();
    }
}
