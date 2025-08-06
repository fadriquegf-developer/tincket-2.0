<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Event;

class EventApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Display a list of events with future sessions
     *
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $query = $this->allBuilder($request);

        // only show events from promotors if they select one of yours taxonomies
        $brandId = request()->get('brand.id', null);
        $query->whereHas('taxonomies', function ($query) use ($brandId) {
            // events has some taxonomi from current brand
            $query->where('brand_id', $brandId);
        });

        if ($request->get('page', false)) {
            // filter only show old events
            if ($request->get('history')) {
                $query->doesntHave('next_sessions');
            }

            // calcule total events 
            $total = $query->count();

            //paginate results
            $perPage = $request->get('per_page') ?? 16;
            $query->offset(((int) $request->get('page') - 1) * $perPage)->limit($perPage);

            if ($request->get('randomly')) {
                $query->inRandomOrder();
            } else {
                $query->orderBy('publish_on', 'DESC');
            }

            $data = $query
                ->get()
                ->each(function ($event) {
                    \App\Services\Api\EventService::addAttributes($event);
                });

            if ($request->get('sort_by_next')) {
                $data = $data->sortBy('next_session.starts_on');
            }

            return  $this->json([
                'data' => $data,
                'total' =>  $total,
                'per_page' => $perPage
            ]);
        }

        return  $this->json($query->get()
            ->sortBy('next_session.starts_on')
            ->each(function ($event) {
                \App\Services\Api\EventService::addAttributes($event);
            }));
    }

    /**
     * Display details of a given Event with its sessions. All of them, ordered
     * by starting date
     *
     * @param  int  $id
     */
    public function show(\Illuminate\Http\Request $request, $id)
    {

        $event = $this->allBuilder($request)
            ->where('id', $id)
            ->firstOrFail();

        \App\Services\Api\EventService::addAttributes($event);

        return $this->json($event);
    }

    /**
     * Returns sessions of an event ordered by start_on ASC
     *
     * @param Event $event
     */
    public function getSessions(Event $event)
    {
        $event->checkBrandPartnership();

        $builder = request()->get('show_expired', false)
            ? $event->sessions()
            : $event->next_sessions();

        $sessions = $builder->with(['space.location'])->where('visibility', 1)->orderBy('starts_on', 'ASC')->get();

        return $this->json($sessions);
    }



    /**
     * The index and show method need to start from a common query. This method
     * provides this Query and every method applies afterward their own filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function allBuilder(\Illuminate\Http\Request $request)
    {
        $expired_sessions = $request->get('show_expired', false);
        $builder = Event::published()->ownedByPartneship()
            ->with('taxonomies')
            ->with('sessions.space.location');

        /* Filters */
        // date range
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
            // filter next sessions
            $builder->where(function ($query) {
                $query->whereHas('next_sessions', function ($query) {
                    return $query->has('space.location');
                })->orWhereHas('sessions_no_finished', function ($query) {
                    return $query->where('private', 1);
                });
            });
        }

        // text
        if ($request->get('search')) {
            $search = strtolower($request->get('search'));

            $builder->where(function ($query) use ($search) {
                // encoded name especial characters
                $aux = str_replace(['\\', '%', '"'], ['\\\\', '\%', ''], json_encode($search));
                return $query
                    ->whereRaw('LOWER(`name`) LIKE ?', ["%$aux%"])
                    ->orWhereRaw('LOWER(`name`) LIKE ?', ["%$search%"]);
            });
        }

        // payment
        if ($request->get('payment')) {
            $taxonomies = explode(',', $request->get('payment'));
            $builder->whereHas('taxonomies', function ($query) use ($taxonomies) {
                $query->whereIn('taxonomy_id', $taxonomies);
            });
        }

        // taxonomy
        if ($request->get('taxonomies')) {
            $taxonomies = explode(',', $request->get('taxonomies'));
            $builder->whereHas('taxonomies', function ($query) use ($taxonomies) {
                $query->whereIn('taxonomy_id', $taxonomies);
            });
        }

        // region/city
        if ($request->get('cities')) {
            $cities = $request->get('cities');
            if (is_string($cities)) {
                $cities = explode(',',  $cities);
            }
            $builder->whereHas('next_sessions.space.location', function ($query) use ($cities) {
                $query->whereIn('city_id', $cities);
            });
        }

        if ($request->get('regions')) {
            $regions = $request->get('regions');
            if (is_string($regions)) {
                $regions = explode(',',  $regions);
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
}
