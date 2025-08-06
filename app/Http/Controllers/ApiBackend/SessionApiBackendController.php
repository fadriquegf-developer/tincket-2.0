<?php

namespace App\Http\Controllers\ApiBackend;

use App\Models\Session;
use App\Http\Resources\SessionStaticsCollection;

/**
 * Description of SessionApiBackendController
 *
 * @author miquel
 */
class SessionApiBackendController extends \App\Http\Controllers\Controller
{

    public function getConfiguration(Session $session)
    {

        $session->checkBrandOwnership();

        $session->load('configuration.zones');

        if ($session->configuration) {
            $session->configuration->setAttribute('free_positions', $session->count_free_positions);
            $session->configuration->setAttribute('zoom', $session->space->zoom);
        }

        // update cache
        $slot_cache = new \App\Services\Api\SlotCacheService($session);
        //$slot_cache->freeExpiredSlotsSession();
        $slot_cache->showPrivateRates(true)->getSlotsState();

        return $session->configuration;
    }

    public function search()
    {
        $builder = Session::ownedByBrandOrPartneship()
            ->with(['event' => function ($query) {
                return $query->select([
                    'id',
                    'brand_id',
                    'name',
                ]);
            }])
            ->select([
                'id',
                'brand_id',
                'name',
                'starts_on',
                'event_id'
            ])
            ->orderBy('starts_on', 'DESC');

        if (!request()->get('show_expired', false)) {
            $builder->where('starts_on', '>', \Carbon\Carbon::now())
                ->where('inscription_starts_on', '<', \Carbon\Carbon::now())
                ->where('inscription_ends_on', '>', \Carbon\Carbon::now());
        }

        if (request()->get('with_sales', false)) {
            // solo sesiones que tengan al menos una inscripción pagada
            $builder->whereHas('inscriptions', function ($query) {
                $query->paid();
            });
        }

        return new SessionStaticsCollection($builder->get());
    }
}
