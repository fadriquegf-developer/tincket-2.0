<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Event;
use App\Scopes\BrandScope;
use Carbon\Carbon;

class EventApiController extends \App\Http\Controllers\Api\ApiController
{
    /**
     * Display details of a given Event with its sessions.
     */
    public function show(\Illuminate\Http\Request $request, $id)
    {
        $brand = $request->get('brand');

        $partnersIds = $brand->children->pluck('id')->toArray();
        $allowedBrandIds = array_merge([$brand->id], $partnersIds);

        $event = Event::withoutGlobalScope(BrandScope::class)
            ->where('id', $id)
            ->where('publish_on', '<', Carbon::now())
            ->where('is_active', true)
            ->whereIn('brand_id', $allowedBrandIds)
            ->with([
                'brand.capability',
                'taxonomies',
                'sessions' => function ($q) use ($request) {
                    $q->withoutGlobalScope(BrandScope::class);
                    $q->with([
                        'brand.capability',
                        'space' => function ($spaceQuery) {
                            $spaceQuery->withoutGlobalScope(BrandScope::class)
                                ->with(['location' => function ($locQuery) {
                                    $locQuery->withoutGlobalScope(BrandScope::class);
                                }]);
                        }
                    ]);
                    $q->with(['allRates']);
                    $q->where('visibility', 1);

                    if (!$request->get('show_expired', false)) {
                        $q->where('ends_on', '>', Carbon::now());
                    }

                    $q->orderBy('starts_on', 'ASC');
                }
            ])
            ->first();

        if (!$event) {
            abort(404, 'Event not found or inactive');
        }

        \App\Services\Api\EventService::addAttributes($event);

        $attributes = $event->getAttributes();
        $eventData = $event->toArray();

        if (!isset($eventData['next_session']) && isset($attributes['next_session'])) {
            $eventData['next_session'] = $attributes['next_session'];
        }
        if (!isset($eventData['first_session']) && isset($attributes['first_session'])) {
            $eventData['first_session'] = $attributes['first_session'];
        }
        if (!isset($eventData['last_session']) && isset($attributes['last_session'])) {
            $eventData['last_session'] = $attributes['last_session'];
        }
        if (!isset($eventData['rate_from']) && isset($attributes['rate_from'])) {
            $eventData['rate_from'] = $attributes['rate_from'];
        }
        if (!isset($eventData['price_from']) && isset($attributes['price_from'])) {
            $eventData['price_from'] = $attributes['price_from'];
        }

        return $this->json($eventData);
    }

    /**
     * Display a list of events with future sessions
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $brand = $request->get('brand');
        $hasPartners = $brand->children()->count() > 0;

        $query = $this->allBuilder($request);

        // Solo filtrar por taxonomías si NO tiene partners O si hay filtro explícito
        if (!$hasPartners || $request->get('taxonomies')) {
            $brandId = $brand->id;
            $query->whereHas('taxonomies', function ($query) use ($brandId) {
                $query->where('brand_id', $brandId);
            });
        }

        if ($request->get('page', false)) {
            if ($request->get('history')) {
                $query->doesntHave('next_sessions');
            }

            $total = $query->count();
            $perPage = $request->get('per_page') ?? 16;
            $query->offset(((int) $request->get('page') - 1) * $perPage)->limit($perPage);

            if ($request->get('randomly')) {
                $query->inRandomOrder();
            } else {
                $query->orderBy('publish_on', 'DESC');
            }

            $data = $query->get()->each(function ($event) {
                \App\Services\Api\EventService::addAttributes($event);
            });

            if ($request->get('sort_by_next')) {
                $data = $data->sortBy('next_session.starts_on');
            }

            return $this->json([
                'data' => $data,
                'total' => $total,
                'per_page' => $perPage
            ]);
        }

        $results = $query->get()
            ->sortBy('next_session.starts_on')
            ->each(function ($event) {
                \App\Services\Api\EventService::addAttributes($event);
            });

        return $this->json($results);
    }

    /**
     * Returns sessions of an event ordered by start_on ASC
     */
    public function getSessions(Event $event)
    {
        $brand = request()->get('brand');
        $allowedBrandIds = array_merge(
            [$brand->id],
            $brand->children->pluck('id')->toArray()
        );

        if (!in_array($event->brand_id, $allowedBrandIds)) {
            abort(404, 'Event does not belong to current brand or partners');
        }

        $builder = request()->get('show_expired', false)
            ? $event->sessions()
            : $event->next_sessions();

        // Remover BrandScope de sessions, space Y location
        $sessions = $builder
            ->withoutGlobalScope(BrandScope::class)
            ->with([
                'space' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with(['location' => function ($locQ) {
                            $locQ->withoutGlobalScope(BrandScope::class);
                        }]);
                },
                // Agregar carga de tarifas públicas
                'allRates' => function ($ratesQuery) {
                    $ratesQuery->where('is_public', true)
                        ->with('rate:id,name')
                        ->orderBy('price', 'ASC');
                }
            ])
            ->where('visibility', 1)
            ->orderBy('starts_on', 'ASC')
            ->get();

        return $this->json($sessions);
    }

    /**
     * Endpoint para brands con partners (Ticketara)
     */
    public function allNextEvents(\Illuminate\Http\Request $request)
    {
        $brand = request()->get('brand');
        $hasPartners = $brand->children()->count() > 0;

        $cacheKey = "events:all_next:{$brand->id}:has_partners:{$hasPartners}";
        $cacheDuration = 300;

        return \Cache::remember($cacheKey, $cacheDuration, function () use ($brand, $hasPartners) {

            $query = Event::withoutGlobalScope(BrandScope::class)
                ->select('events.*')
                ->where('events.publish_on', '<', Carbon::now())
                ->where('events.is_active', true)
                ->whereNull('events.deleted_at')
                ->whereIn('events.brand_id', $this->getPartnershipedBrandsIds($brand));

            $query->join('sessions', function ($join) {
                $join->on('sessions.event_id', '=', 'events.id')
                    ->where('sessions.ends_on', '>', Carbon::now())
                    ->where('sessions.visibility', 1)
                    ->whereNull('sessions.deleted_at');
            })->groupBy('events.id');

            if (!$hasPartners) {
                $brandId = $brand->id;
                $query->join('classifiables', function ($join) use ($brandId) {
                    $join->on('classifiables.classifiable_id', '=', 'events.id')
                        ->where('classifiables.classifiable_type', '=', 'App\\Models\\Event')
                        ->join('taxonomies', function ($taxJoin) use ($brandId) {
                            $taxJoin->on('taxonomies.id', '=', 'classifiables.taxonomy_id')
                                ->where('taxonomies.brand_id', '=', $brandId);
                        });
                });
            }

            $query->orderBy('events.publish_on', 'DESC');
            $results = $query->limit(100)->get();

            $results->load([
                'brand.capability',
                'sessions' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with('brand.capability')
                        ->where('ends_on', '>', Carbon::now())
                        ->where('visibility', 1)
                        ->orderBy('starts_on', 'ASC')
                        ->limit(3);
                },
                'taxonomies'
            ]);

            $results->each(function ($event) {
                \App\Services\Api\EventService::addAttributes($event);
            });

            $results = $results->sortBy('next_session.starts_on')->values();

            return $this->json($results);
        });
    }

    /**
     * Query builder común para index()
     */
    private function allBuilder(\Illuminate\Http\Request $request)
    {
        $expired_sessions = $request->get('show_expired', false);

        $builder = Event::published()
            ->where('is_active', true)
            ->ownedByPartneship()
            ->with([
                'brand.capability',
                'taxonomies',
                'sessions' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with([
                            'brand.capability',
                            'space.location'
                        ]);
                }
            ]);

        if ($request->get('date_from') || $request->get('date_to')) {
            $from = $request->get('date_from');
            $to = $request->get('date_to');

            $builder->whereHas('next_sessions', function ($query) use ($from, $to) {
                if ($from) {
                    $query->where('starts_on', '>=', $from);
                }
                if ($to) {
                    $query->where('ends_on', '<=', $to . ' 23:59:59');
                }
                return $query;
            });
        } elseif (!$expired_sessions) {
            $builder->where(function ($query) {
                $query->whereHas('next_sessions', function ($query) {
                    return $query->has('space.location');
                })->orWhereHas('sessions_no_finished', function ($query) {
                    return $query->where('private', 1);
                });
            });
        }

        if ($request->get('search')) {
            $search = strtolower($request->get('search'));
            $builder->where(function ($query) use ($search) {
                $aux = str_replace(['\\', '%', '"'], ['\\\\', '\%', ''], json_encode($search));
                return $query
                    ->whereRaw('LOWER(`name`) LIKE ?', ["%$aux%"])
                    ->orWhereRaw('LOWER(`name`) LIKE ?', ["%$search%"]);
            });
        }

        if ($request->get('payment')) {
            $taxonomies = explode(',', $request->get('payment'));
            $builder->whereHas('taxonomies', function ($query) use ($taxonomies) {
                $query->whereIn('taxonomy_id', $taxonomies);
            });
        }

        if ($request->get('taxonomies')) {
            $taxonomies = explode(',', $request->get('taxonomies'));
            $builder->whereHas('taxonomies', function ($query) use ($taxonomies) {
                $query->whereIn('taxonomy_id', $taxonomies);
            });
        }

        if ($request->get('cities')) {
            $cities = $request->get('cities');
            if (is_string($cities)) {
                $cities = explode(',', $cities);
            }
            $builder->whereHas('next_sessions.space.location', function ($query) use ($cities) {
                $query->whereIn('city_id', $cities);
            });
        }

        if ($request->get('regions')) {
            $regions = $request->get('regions');
            if (is_string($regions)) {
                $regions = explode(',', $regions);
                $builder->whereHas('next_sessions.space.location.town.region', function ($query) use ($regions) {
                    $query->whereIn('id', $regions);
                });
            } else {
                $builder->whereHas('next_sessions.space.location.town.region', function ($query) use ($regions) {
                    $query->where('id', $regions);
                });
            }
        }

        return $builder;
    }

    /**
     * Helper para obtener IDs de brands permitidos
     */
    private function getPartnershipedBrandsIds($brand)
    {
        $brandsId = collect([$brand->id]);
        $brandsId = $brandsId->merge($brand->children->pluck('id'));
        return $brandsId->unique()->values()->toArray();
    }
}
