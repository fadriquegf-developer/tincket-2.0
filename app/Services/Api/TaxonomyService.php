<?php

namespace App\Services\Api;

use App\Models\Taxonomy;
use Illuminate\Support\Str;

/**
 * This Taxonomy Service takes care of 'searching' related
 * entities to the Taxonomy. If a method is defined for the entity
 * it will run and then parse/add/set its attributes with the use of
 * the own entity service.
 *
 * @author jaumemk
 */
class TaxonomyService extends AbstractService
{

    private $taxonomy;

    private $types = [
        'events',
        'next_events'
    ];

    public function __construct(Taxonomy $taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    public function setRelation($type)
    {
        $entities = $this->taxonomy->{$type};

        if (is_null($entities)) {
            abort(404, "Relation not found for " . Str::studly($type));
        }

        if (
            $entities->count() > 0 &&
            in_array($type, $this->types) &&
            method_exists($this, 'set' . Str::studly($type) . 'Attributes')
        ) {
            $this->{'set' . Str::studly($type) . 'Attributes'}($entities);
        }

        return $this->taxonomy->setRelation($type, $entities);
    }

    // TODO: maybe this method can be ommited and replaced by another one
    // who will search the service directly if the static method exists.

    private function setEventsAttributes(&$events)
    {
        $events->each(function ($event) {
            \App\Services\Api\EventService::addAttributes($event);
        });

        $events = $events->sortBy('next_session.starts_on');
    }

    private function setNextEventsAttributes(&$events)
    {
        $this->setEventsAttributes($events);
    }
}
