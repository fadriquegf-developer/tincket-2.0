<?php

namespace App\Http\Controllers\ApiBackend\Statistics;

use Carbon\Carbon;
use App\Models\Cart;
use App\Models\Inscription;
use Illuminate\Http\Request;
use App\Services\Payment\Impl\PaymentTicketOfficeService;

class StatisticsBalanceController extends \App\Http\Controllers\Controller
{
    public function get(Request $request)
    {
        /* ▸ 1) Validamos los parámetros que llegan */
        $request->validate([
            'from'      => ['nullable', 'regex:/^\d{4}-?\d{2}-?\d{2}$/'],
            'to'        => ['nullable', 'regex:/^\d{4}-?\d{2}-?\d{2}$/'],
            'breakdown' => ['required', 'in:U,E,P'],
        ]);

        /* ▸ 2) Parseamos la fecha acepte o no guiones */
        $start_date = $this->parseDate($request->input('from'), true);   // inicio
        $end_date   = $this->parseDate($request->input('to'),   false);  // fin

        /* ▸ 3) Elegimos el desglose */
        $balance = match ($request->breakdown) {
            'E'   => $this->breakdownByEvent($start_date, $end_date),
            'P'   => $this->breakdownByPromoter($start_date, $end_date),
            default => $this->breakdownByUser($start_date, $end_date),
        };

        return response()->json($balance);
    }

    /** ------------------------------------------------------------------
     *  Convierte '20250703' o '2025-07-03' en Carbon y aplica start/end.
     * -----------------------------------------------------------------*/
    private function parseDate(?string $value, bool $isStart = true): Carbon
    {
        if (empty($value) || $value === 'null') {
            return $isStart ? Carbon::minValue() : Carbon::maxValue();
        }

        $format = str_contains($value, '-') ? 'Y-m-d' : 'Ymd';
        $date   = Carbon::createFromFormat($format, $value);

        return $isStart ? $date->startOfDay() : $date->endOfDay();
    }

    private function breakdownByUser($start_date, $end_date)
    {
        // Obtener vendedores: usuarios y aplicaciones del brand actual
        $brand = get_current_brand();
        $sellers = $brand->users->merge($brand->applications);

        $balance = $sellers->map(function ($seller) use ($start_date, $end_date) {
            // Obtenemos los carritos confirmados del vendedor, cargando el último pago confirmado
            $seller_carts = Cart::ownedByBrand()->confirmed()
                ->whereSellerType(get_class($seller))
                ->whereSellerId($seller->id)
                ->with('confirmedPayment')
                ->get()
                ->filter(function ($cart) use ($start_date, $end_date) {
                    $payment = $cart->confirmedPayment;
                    if (!$payment || !$payment->paid_at) {
                        return false;
                    }
                    // Aseguramos la conversión a Carbon para la comparación
                    return Carbon::parse($payment->paid_at) >= $start_date &&
                        Carbon::parse($payment->paid_at) <= $end_date;
                });

            $total = 0;
            $totalCash = 0;
            $totalCard = 0;

            // Recorremos cada carrito y sumamos el importe a partir de gateway_response
            foreach ($seller_carts as $cart) {
                $gateway_response = json_decode($cart->confirmedPayment->gateway_response);
                if (isset($gateway_response->Ds_Amount)) {
                    // Se asume que Ds_Amount viene en céntimos, se convierte a euros
                    $price = $gateway_response->Ds_Amount / 100;
                    $total += $price;
                    if (isset($gateway_response->payment_type)) {
                        switch ($gateway_response->payment_type) {
                            case PaymentTicketOfficeService::CARD:
                                $totalCard += $price;
                                break;
                            case PaymentTicketOfficeService::CASH:
                                $totalCash += $price;
                                break;
                        }
                    }
                }
            }

            return [
                'name' => ($seller->name ?? $seller->code_name) . ' (' . $seller->email . ')',
                'from'       => $start_date->toDateString(),
                'to'         => $end_date->toDateString(),
                'count'      => $seller_carts->count(),
                'totalCash'  => $totalCash,
                'totalCard'  => $totalCard,
                'sum'        => $total,
            ];
        });

        return $balance;
    }

    private function breakdownByEvent($start_date, $end_date)
    {
        $brandId = get_current_brand()->id;

        // Cargamos las inscripciones con sus relaciones
        $inscriptions = Inscription::with([
            'session.event',
            'cart.client',
            'cart.seller',
            'cart.confirmedPayment'
        ])
            ->whereHas('session', function ($query) {
                $query->OwnedByBrandOrPartneship();
            })
            ->whereHas('cart', function ($query) use ($brandId) {
                $query->where('brand_id', $brandId)
                    ->whereNotNull('confirmation_code');
            })
            ->get()
            // Filtramos para quedarnos solo con inscripciones cuyo pago confirmado esté en rango
            ->filter(function ($inscription) use ($start_date, $end_date) {
                $payment = $inscription->cart->confirmedPayment;
                if (!$payment || !$payment->paid_at) {
                    return false;
                }
                return Carbon::parse($payment->paid_at) >= $start_date &&
                    Carbon::parse($payment->paid_at) <= $end_date;
            });

        // Agrupar por el nombre del evento y calcular totales
        $balance = $inscriptions->groupBy(function ($inscription) {
            return $inscription->session->event->name;
        })->map(function ($group) use ($start_date, $end_date) {
            return [
                'name'  => $group->first()->session->event->name,
                'count' => $group->count(),
                'sum'   => $group->sum('price_sold'),
                'from'  => $start_date->toDateString(),
                'to'    => $end_date->toDateString(),
            ];
        })->values();

        return $balance;
    }

    private function breakdownByPromoter($start_date, $end_date)
    {
        $brandId = get_current_brand()->id;

        $inscriptions = Inscription::with([
            'session.event',
            'cart.client',
            'cart.seller',
            'cart.confirmedPayment'
        ])
            ->whereHas('session', function ($query) {
                $query->OwnedByBrandOrPartneship();
            })
            ->whereHas('cart', function ($query) use ($brandId) {
                $query->where('brand_id', $brandId)
                    ->whereNotNull('confirmation_code');
            })
            ->get()
            ->filter(function ($inscription) use ($start_date, $end_date) {
                $payment = $inscription->cart->confirmedPayment;
                if (!$payment || !$payment->paid_at) {
                    return false;
                }
                return Carbon::parse($payment->paid_at) >= $start_date &&
                    Carbon::parse($payment->paid_at) <= $end_date;
            });

        $balance = $inscriptions->groupBy(function ($inscription) {
            return $inscription->session->brand->name;
        })->map(function ($group) use ($start_date, $end_date) {
            return [
                'name'  => $group->first()->session->brand->name,
                'count' => $group->count(),
                'sum'   => $group->sum('price_sold'),
                'from'  => $start_date->toDateString(),
                'to'    => $end_date->toDateString(),
            ];
        })->values();

        return $balance;
    }
}
