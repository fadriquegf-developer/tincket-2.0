<?php

namespace App\Services\Api;

use App\Models\Event;
use App\Models\Session;
use App\Models\AssignatedRate;

class EventService extends AbstractService
{
    /** @var Event */
    private $event;

    public function __construct(Event $event)
    {
        $this->event = $event;

        if (!$this->event) {
            throw new \Exception("App\Services\Api\EventService was instantiated without an event");
        }
    }

    /**
     * Return the next session for an event using loaded sessions
     */
    public function getNextSession()
    {
        // Buscar en las sesiones cargadas primero
        if ($this->event->sessions && $this->event->sessions->count() > 0) {
            return $this->event->sessions
                ->where('visibility', 1)
                ->filter(function ($session) {
                    return $session->ends_on > \Carbon\Carbon::now();
                })
                ->sortBy('starts_on')
                ->first();
        }

        // Fallback: hacer query
        return $this->event->sessions()
            ->with(['space.location'])
            ->where('ends_on', '>', \Carbon\Carbon::now())
            ->orderBy('starts_on', 'ASC')
            ->first();
    }

    public function getFirstSession()
    {
        // Buscar en las sesiones cargadas primero
        if ($this->event->sessions && $this->event->sessions->count() > 0) {
            return $this->event->sessions
                ->where('visibility', 1)
                ->sortBy('starts_on')
                ->first();
        }

        return $this->event->first_session;
    }

    public function getLastSession()
    {
        // Buscar en las sesiones cargadas primero
        if ($this->event->sessions && $this->event->sessions->count() > 0) {
            return $this->event->sessions
                ->where('visibility', 1)
                ->sortByDesc('starts_on')
                ->first();
        }

        return $this->event->last_session;
    }

    public function getPriceFrom(): float
    {
        $sessions = $this->event->sessions;

        if (!$sessions || $sessions->count() === 0) {
            return 0.0;
        }

        $minRate = $sessions->min('general_rate');

        return (float) ($minRate ? $minRate->price : 0);
    }

    public function getRateFrom()
    {
        $sessions = $this->event->sessions;

        if (!$sessions || $sessions->count() === 0) {
            return null;
        }

        $event_sessions = $sessions->where('visibility', 1);

        if ($event_sessions->count() === 0) {
            return null;
        }

        $highest_sessions_rates = $event_sessions->map(function ($session) {
            $rate = AssignatedRate::whereSessionId($session->id)
                ->whereIsPublic(true)
                ->orderBy('price', 'DESC')
                ->with('rate')
                ->first();

            if ($rate) {
                $rate->setAttribute('pivot', ['price' => $rate->price]);
                if ($rate->rate) {
                    $rate->setAttribute('name', json_decode($rate->rate->getAttributes()['name']));
                }
                unset($rate->rate);
            }

            return $rate;
        })->filter();

        $highest_rate = $highest_sessions_rates->sortByDesc('price')->values()->first();

        return $highest_rate;
    }

    public static function addAttributes($event)
    {
        $service = (new static($event));

        $event->setAttribute('next_session', $service->getNextSession());
        $event->setAttribute('first_session', $service->getFirstSession());
        $event->setAttribute('last_session', $service->getLastSession());
        $event->setAttribute('price_from', $service->getPriceFrom());
        $event->setAttribute('rate_from', $service->getRateFrom());
    }
}
