<?php

namespace App\Services\Api;

use App\Models\Event;
use App\Models\Session;
use App\Models\AssignatedRate;

/**
 * The API endpoints needs to play in some cases some more logic than Model
 * accessors.
 *
 * This service encapsulates the operation that could be delegated to Model
 * but in order to not limitate model functionalities with restrictive API
 * conditions, this logic is moved in these services
 *
 * @author miquel
 */
class EventService extends AbstractService
{

    /** @var Event */
    private $event;

    public function __construct(Event $event)
    {
        $this->event = $event;

        if (!$this->event)
        {
            throw new \Exception("App\Services\Api\EventService was instancialed without and event");
        }
    }

    /**
     * Return the next session for an event.
     *
     * In API endpoints we want to ensure that all sessions has an space and location
     * assigned.
     *
     * This is the reason we use this Service instead of Model Accessor which
     * returns alls the next sessions without any condition
     *
     * @return Session
     * @throws \Exception
     */
    public function getNextSession()
    {
        return $this->event->sessions()
                        ->with(['space.location'])
                        ->where('ends_on', '>', \Carbon\Carbon::now())
                        ->orderBy('starts_on', 'ASC')
                        ->first();
    }

    public function getFirstSession()
    {
        return $this->event->first_session;
    }

    public function getLastSession()
    {
        return $this->event->last_session;
    }

    public function getPriceFrom(): float
    {
        return (float) ($this->event->sessions->min('general_rate') ? $this->event->sessions->min('general_rate')->price : 0);
    }

    public function getRateFrom()
    {

        // TODO: Maybe/Improvement
        // We can delegate some logic to the DB to retrieve the
        // $highest_rate per this event.
        $event_sessions = $this->event->sessions()->where('visibility', 1)->get();
        $highest_sessions_rates = $event_sessions->map(function($session)
        {
            $rate = AssignatedRate::whereSessionId($session->id)
                    ->whereIsPublic(true)
                    ->orderBy('price', 'DESC')
                    ->with('rate')
                    ->first();

            if ($rate)
            {
                // Miquel says: we set this attributes to make changes compatible to 
                // all old API consumers            
                $rate->setAttribute('pivot', ['price' => $rate->price]);
                if($rate->rate){
                    $rate->setAttribute('name', json_decode($rate->rate->getAttributes()['name']));
                }
                // -- 

                unset($rate->rate);
            }

            return $rate;
        });
        $highest_rate = $highest_sessions_rates->sortByDesc('price')->values()->first();

        return $highest_rate;
    }

    public static function addAttributes($event)
    {

        $service = (new static($event));

        $event->setAttribute('next_session', $service->getNextSession());
        $event->setAttribute('first_session', $service->getFirstSession());
        $event->setAttribute('last_session', $service->getLastSession());
        // TODO: price_from Deprecated attribute.
        $event->setAttribute('price_from', $service->getPriceFrom());
        $event->setAttribute('rate_from', $service->getRateFrom());
    }

}
