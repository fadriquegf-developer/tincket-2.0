<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Event;
use App\Models\Pack;
use App\Services\RedisSlotsService;
use Illuminate\Support\Facades\DB;

class PackApiController extends \App\Http\Controllers\Api\ApiController
{
    /**
     * Display a list of packs
     */
    public function index()
    {
        return $this->json($this->allBuilder()->get());
    }

    /**
     * Display details of a given Pack with its sessions optimized
     */
    public function show($id)
    {
        $pack = $this->allBuilder()
            ->where('id', $id)
            ->with([
                'rules' => fn($q) => $q->orderBy('id'),
                'sessions' => fn($q) => $q
                    ->where('ends_on', '>', now())
                    ->with([
                        'event:id,name,slug,description,lead,image,banner',
                        'space.location',
                        'allRates.rate:id,name'
                    ])
            ])
            ->firstOrFail();

        $sessionIds = $pack->sessions->pluck('id')->unique();

        // Crear instancias del servicio para cada sesión (eficiente con singleton)
        $slotServices = [];
        foreach ($pack->sessions as $session) {
            $slotServices[$session->id] = RedisSlotsService::for($session);
        }

        $generalRates = DB::table('assignated_rates as ar')
            ->join('rates as r', 'r.id', '=', 'ar.rate_id')
            ->whereIn('ar.session_id', $sessionIds)
            ->where(function ($query) {
                $query->where(function ($sub) {
                    $sub->where('ar.is_private', true)
                        ->where(function ($dates) {
                            $dates->whereNull('ar.available_since')
                                ->orWhere('ar.available_since', '<=', now());
                        })
                        ->where(function ($dates) {
                            $dates->whereNull('ar.available_until')
                                ->orWhere('ar.available_until', '>=', now());
                        });
                })
                    ->orWhere('ar.is_public', true);
            })
            ->select('ar.session_id', 'ar.price', 'ar.is_private', 'ar.rate_id', 'r.name as rate_name')
            ->get();

        $generalRates = $generalRates
            ->groupBy('session_id')
            ->map(function ($rates) {
                $privateRates = $rates->where('is_private', true);
                $publicRates = $rates->where('is_private', false);

                if ($privateRates->isNotEmpty()) {
                    return $privateRates->sortByDesc('price')->first();
                }

                if ($publicRates->isNotEmpty()) {
                    return $publicRates->sortByDesc('price')->first();
                }

                return $rates->sortByDesc('price')->first();
            });

        // Procesar sesiones
        $pack->sessions->each(function ($session) use ($generalRates, $slotServices) {
            $generalRate = $generalRates->get($session->id);

            if ($generalRate) {
                $session->setAttribute('price', $generalRate->price);
                $session->setAttribute('general_rate', $generalRate);
            }

            // ⭐ CRÍTICO: Añadir count_free_positions usando el servicio
            $service = $slotServices[$session->id];
            $availablePositions = $service->getAvailableWebPositions();
            $session->setAttribute('count_free_positions', $availablePositions);

            // Ocultar event para evitar recursión
            $session->makeHidden(['event']);
        });

        // Filtrar sesiones sin tarifa válida
        $validSessions = $pack->sessions->filter(fn($s) => $s->getAttribute('price') !== null);

        // Agrupar por evento
        $events = $validSessions
            ->groupBy('event_id')
            ->map(function ($sessions, $eventId) {
                // Obtener una instancia limpia del evento
                $event = Event::select('id', 'name', 'slug', 'description', 'lead', 'image', 'banner')
                    ->find($eventId);

                if (!$event) return null;

                // Asignar sesiones procesadas
                $event->setRelation('sessions', $sessions->values());

                return $event;
            })
            ->filter()
            ->values();

        // Asignar eventos al pack
        $pack->unsetRelation('sessions');
        $pack->setRelation('events', $events);

        return $this->json($pack);
    }

    /**
     * The index and show method need to start from a common query
     */
    private function allBuilder()
    {
        $builder = Pack::published()
            ->ownedByBrand()
            ->has('sessions')
            ->where('starts_on', '<', \Carbon\Carbon::now())
            ->where(function ($q) {
                $q->where('ends_on', '>', \Carbon\Carbon::now())
                    ->orWhereNull('ends_on');
            });

        return $builder;
    }
}
