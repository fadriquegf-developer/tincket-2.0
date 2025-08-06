<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Pack;

class PackApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Display a list of packs
     *
     */
    public function index()
    {
        return $this->json($this->allBuilder()->get());
    }

    /**
     * Display details of a given Pack with its sessions. All of them, ordered
     * by starting date
     *
     * @param  int  $id
     */
    public function show($id)
    {
        $pack = $this->allBuilder()
            ->where('id', $id)
            ->with([
                'rules' => function ($query) {
                    $query->orderBy('id');
                }
            ])
            ->firstOrFail();

        // We set the price of each session in the current Pack
        $pack->events->each(function ($event) {
            $sessions = $event->next_sessions->filter(function ($session) {
                return $session->general_rate;
            });

            if ($sessions->isNotEmpty()) {
                $event->setAttribute('sessions', $sessions);
                $sessions->each(function ($session) {
                    $session->load('space.location');
                    $session->setAttribute('price', $session->general_rate->price);
                });
            }
        });

        $pack->setRelation('events', $pack->events->filter(function ($event) {
            return $event->sessions->isNotEmpty();
        }));


        return $this->json($pack);
    }

    /**
     * The index and show method need to start from a common query. This method
     * provides this Query and every method applies afterward their own filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function allBuilder()
    {
        $builder = Pack::published()
                ->ownedByBrand()
                ->has('sessions')
                ->where('starts_on', '<', \Carbon\Carbon::now())
                ->where(function($q){
                    // "ends_on is not expired" OR "ends_on is null and still pack still has active sessions"
                    $q->where('ends_on', '>', \Carbon\Carbon::now())
                    ->orWhereNull('ends_on');
                 });

        return $builder;
    }
}
