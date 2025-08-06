<?php

namespace App\Http\Controllers\ApiBackend\Statistics;

use App\Models\Inscription;
use Illuminate\Http\Request;
use App\Services\Payment\Impl\PaymentTicketOfficeService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; 


class StatisticsSalesController extends \App\Http\Controllers\Controller
{

    public function get(Request $request)
    {
        $sessions_id = explode(',', $request->get('session_id', ''));
        $sales_range = json_decode($request->get('sales_range', '{}'));

        // Construir la consulta sin el join directo a payments
        $query = Inscription::with([
            'session.event',
            'cart.client',
            'cart.seller',
            'cart.confirmedPayment',
            'group_pack.pack',
            'gift_card'
        ])
            ->whereIn('session_id', $sessions_id)
            ->whereHas('cart', function ($query) {
                $query->whereNotNull('confirmation_code')
                    ->whereNull('deleted_at');
            })
            ->whereNull('inscriptions.deleted_at');

        // Filtro por tipo "TicketOffice" si se especifica breakdown = 'T'
        if ($request->get('breakdown') == 'T') {
            $query->whereHas('cart', function ($query) {
                $query->where('seller_type', 'App\Models\User');
            });
        }

        // Construir los objetos DateTime a partir del sales_range
        $from = (new \DateTime())->setTimestamp($sales_range->from / 1000)
            ->setTimezone(new \DateTimeZone(config('app.timezone')));
        $to = (new \DateTime())->setTimestamp($sales_range->to / 1000)
            ->setTimezone(new \DateTimeZone(config('app.timezone')));

        // Obtener las inscripciones y filtrar por la fecha de pago del último payment (confirmedPayment)
        $inscriptions = $query->get()->filter(function ($inscription) use ($from, $to) {
            $payment = $inscription->cart->confirmedPayment;
            if (!$payment || !$payment->paid_at) {
                return false;
            }
            // Convertir paid_at a un objeto Carbon
            $paidAt = Carbon::parse($payment->paid_at);
            return $paidAt >= $from && $paidAt <= $to;
        });

        $brief = collect();

        if ($request->get('breakdown') == 'R') {
            $brief = $this->breakdownByRate($inscriptions);
        } elseif ($request->get('breakdown') == 'P') {
            $brief = $this->breakdownByPayment($inscriptions);
        } elseif ($request->get('breakdown') == 'U') {
            $brief = $this->breakdownByUser($inscriptions);
        } elseif ($request->get('breakdown') == 'T') {
            $brief = $this->breakdownByTicketOffice($inscriptions);
        }

        $brief = $this->addTotals(collect($brief));

        return response()->json([
            'brief' => $brief,
            'results' => $request->input('summary') === "true" ? [] : $inscriptions
        ]);
    }

    private function breakdownByRate(\Illuminate\Support\Collection $inscriptions)
    {
        $packs =  [
            'name' => 'Packs',
            'count' => 0,
            'amount' => 0,
            'details' => []
        ];
        $countPacks = collect();

        // filter and count info packs
        $inscriptions = $inscriptions->filter(function ($item, $key) use (&$packs, $countPacks) {
            $isPack = $item->group_pack_id;
            if ($isPack) {
                $packs['count']++;
                $packs['amount'] += $item->price_sold;
                $packName = $item->group_pack->pack->name;

                $packId = $item->group_pack->pack->id;
                if (!array_key_exists($item->group_pack->pack->id, $packs['details'])) {
                    // new
                    $packs['details'][$packId] = [
                        'name' => $packName,
                        'count' => 0,
                        'amount' => 0
                    ];
                }

                $packs['details'][$packId]['count']++;
                $packs['details'][$packId]['amount'] += $item->price_sold;

                // count n packs
                $groupPacksIds = $countPacks->get($packId, collect());
                if (!$groupPacksIds->contains($item->group_pack_id)) {
                    $groupPacksIds->push($item->group_pack_id);
                }
                $countPacks->put($packId, $groupPacksIds);
            }
            return !$isPack;
        });

        $nTotalPacks = 0;
        foreach ($countPacks as $packId => $packsIds) {
            $n = $packsIds->count();
            $packs['details'][$packId]['nPacks'] = $n;
            $nTotalPacks += $n;
        }
        $packs['nPacks'] = $nTotalPacks;

        $brief = $inscriptions->groupBy('rate.name')->values()->map(function ($group, $index) {
            return [
                'name' => $group->first()->rate->name,
                'count' => $group->count(),
                'amount' => $group->sum('price_sold'),
                'details' => $group->groupBy('cart.confirmedPayment.gateway')->values()->map(function ($group) {
                    return [
                        'name' => $group->first()->cart->confirmedPayment->gateway ?? 'NA',
                        'count' => $group->count(),
                        'amount' => $group->sum('price_sold'),
                    ];
                })
            ];
        });

        // Fix error: Merging an empty eloquent collection with a non-empty
        // Solution add toBase()
        // https://github.com/laravel/framework/issues/22626
        $brief = $brief->toBase()->merge([
            $packs
        ]);

        return $brief;
    }

    private function breakdownByPayment($inscriptions)
    {
        $brief = $inscriptions->groupBy('cart.confirmedPayment.gateway')->values()->map(function ($group, $index) {
            // if is pack show pack name
            $carts = $group->filter(function ($value, $key) {
                return $value->group_pack_id == NULL;
            });

            $groupPacks = $group->filter(function ($value, $key) {;
                return $value->group_pack_id != NULL;
            });

            $details = $carts->groupBy('rate.name')->values()->map(function ($group) {
                return [
                    'name' => $group->first()->rate->name ?? '',
                    'count' => $group->count(),
                    'amount' => $group->sum('price_sold'),
                ];
            });

            $detailsPacks = $groupPacks->groupBy('group_pack.pack.name')->values()->map(function ($group) {
                return [
                    'name' => $group->first()->group_pack->pack->name ?? '',
                    'count' => $group->count(),
                    'amount' => $group->sum('price_sold'),
                ];
            });

            $details = $details->merge($detailsPacks);

            return [
                'name' => $group->first()->cart->confirmedPayment->gateway ?? 'NA',
                'count' => $group->count(),
                'amount' => $group->sum('price_sold'),
                'details' => $details
            ];
        });

        return $brief;
    }

    private function addTotals(\Illuminate\Support\Collection $brief)
    {
        return $brief->merge([
            [
                'name' => 'All',
                'count' => $brief->sum('count'),
                'amount' => $brief->sum('amount')
            ]
        ]);
    }

    private function breakdownByUser($inscriptions)
    {
        $brief = $inscriptions->groupBy('cart.seller_id')->values()->map(function ($group, $index) {
            return [
                'name' => $group->first()->cart->seller->name ?? 'NA',
                'count' => $group->count(),
                'amount' => $group->sum('price_sold'),
                'details' => []
            ];
        });

        // need to show rate name in results
        $inscriptions->groupBy('rate.name');

        return $brief;
    }

    private function breakdownByTicketOffice($inscriptions)
    {

        $brief = $inscriptions->groupBy(function ($item, int $key) {
            $payment = json_decode($item->cart->confirmedPayment->gateway_response);
            return isset($payment->payment_type) ? $payment->payment_type : 'NA';
        })->values()->map(function ($group, $index) {
            $payment = json_decode($group->first()->cart->confirmedPayment->gateway_response);
            $paymentType = isset($payment->payment_type) ? $payment->payment_type : 'NA';

            return [
                'name' => $paymentType ? trans('tincket/backend.ticket.payment_type.' . $paymentType) : 'NA',
                'count' => $group->count(),
                'amount' => $group->sum('price_sold'),
                'details' => []
            ];
        });


        // need to show rate name in results
        $inscriptions->groupBy('rate.name');

        return $brief;
    }
}
