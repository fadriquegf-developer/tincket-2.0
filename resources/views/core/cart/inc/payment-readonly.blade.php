@php
    // Cargar el pago con withTrashed si no viene
    if (!isset($payment) && isset($entry)) {
        $payment = $entry->payments()->withTrashed()->whereNotNull('paid_at')->first();
    }

    // Verificar si fue reembolsado
    $isRefunded = $payment && $payment->requires_refund && $payment->refunded_at;
    $isPendingRefund = $payment && $payment->requires_refund && !$payment->refunded_at;
@endphp

<div id="payment" class="card mb-4 border h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            {{ __('backend.cart.inc.payment') }}

            {{-- Badge de estado de reembolso --}}
            @if ($isRefunded)
                <span class="badge bg-success ms-2">
                    <i class="la la-check-circle me-1"></i>
                    {{ __('refund.refunded') ?? 'Reembolsado' }}
                </span>
            @elseif ($isPendingRefund)
                <span class="badge bg-warning text-dark ms-2">
                    <i class="la la-clock me-1"></i>
                    {{ __('refund.pending') ?? 'Pendiente reembolso' }}
                </span>
            @endif
        </h5>
    </div>

    <div class="card-body">
        @if ($payment)
            {{-- ═══════════════════════════════════════════════════════════════ --}}
            {{-- ALERTA DE REEMBOLSO COMPLETADO --}}
            {{-- ═══════════════════════════════════════════════════════════════ --}}
            @if ($isRefunded)
                <div class="alert alert-success mb-3">
                    <div class="d-flex align-items-start">
                        <i class="la la-check-circle la-2x me-3 mt-1"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">
                                {{ __('refund.completed_title') ?? '💰 PAGO REEMBOLSADO' }}
                            </h6>
                            <p class="mb-2">
                                {{ __('refund.refunded_on', ['date' => $payment->refunded_at->format('d/m/Y H:i')]) ?? 'Reembolsado el ' . $payment->refunded_at->format('d/m/Y H:i') }}
                            </p>

                            @if ($payment->refund_reference)
                                <p class="mb-1">
                                    <strong>{{ __('refund.reference') ?? 'Referencia' }}:</strong>
                                    <code>{{ $payment->refund_reference }}</code>
                                </p>
                            @endif

                            @if ($payment->refund_reason)
                                <p class="mb-0 small text-muted">
                                    <strong>{{ __('refund.reason') ?? 'Motivo' }}:</strong>
                                    {{ __('refund.reasons.' . $payment->refund_reason) ?? ucfirst(str_replace('_', ' ', $payment->refund_reason)) }}
                                </p>
                            @endif

                            {{-- Detalles adicionales del reembolso --}}
                            @if ($payment->refund_details)
                                @php
                                    $refundDetails = is_array($payment->refund_details)
                                        ? $payment->refund_details
                                        : json_decode($payment->refund_details, true);
                                @endphp

                                @if (!empty($refundDetails))
                                    <div class="mt-2">
                                        <a class="small text-success" data-bs-toggle="collapse"
                                            href="#refundDetailsCollapse" role="button" aria-expanded="false">
                                            <i class="la la-info-circle me-1"></i>
                                            {{ __('refund.show_details') ?? 'Ver detalles del reembolso' }}
                                        </a>

                                        <div class="collapse mt-2" id="refundDetailsCollapse">
                                            <div class="card card-body bg-light py-2 px-3 small">
                                                @if (isset($refundDetails['requested_by']))
                                                    <div><strong>Solicitado por:</strong>
                                                        {{ $refundDetails['requested_by'] }}</div>
                                                @endif
                                                @if (isset($refundDetails['requested_at']))
                                                    <div><strong>Fecha solicitud:</strong>
                                                        {{ \Carbon\Carbon::parse($refundDetails['requested_at'])->format('d/m/Y H:i') }}
                                                    </div>
                                                @endif
                                                @if (isset($refundDetails['notes']) && $refundDetails['notes'])
                                                    <div><strong>Notas:</strong> {{ $refundDetails['notes'] }}</div>
                                                @endif
                                                @if (isset($refundDetails['cart_total']))
                                                    <div><strong>Importe original:</strong>
                                                        {{ number_format($refundDetails['cart_total'], 2) }} €</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════════ --}}
            {{-- ALERTA DE REEMBOLSO PENDIENTE (no debería verse en eliminados, pero por si acaso) --}}
            {{-- ═══════════════════════════════════════════════════════════════ --}}
            @if ($isPendingRefund)
                <div class="alert alert-warning mb-3">
                    <div class="d-flex align-items-start">
                        <i class="la la-exclamation-triangle la-2x me-3 mt-1"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">
                                {{ __('refund.alert_title') ?? '⚠️ REEMBOLSO PENDIENTE' }}
                            </h6>
                            <p class="mb-0 small">
                                Este pago estaba marcado para reembolso pero no se completó antes de eliminar el
                                carrito.
                            </p>
                            @if ($payment->refund_reason)
                                <p class="mb-0 mt-1 small">
                                    <strong>{{ __('refund.reason') ?? 'Motivo' }}:</strong>
                                    {{ __('refund.reasons.' . $payment->refund_reason) ?? ucfirst(str_replace('_', ' ', $payment->refund_reason)) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════════ --}}
            {{-- DATOS DEL PAGO --}}
            {{-- ═══════════════════════════════════════════════════════════════ --}}
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">
                    {{ __('backend.cart.inc.payment') }} {{ __('backend.cart.inc.code') }}
                </label>
                <div class="col-sm-9">{{ $payment->order_code }}</div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.paidat') }}</label>
                <div class="col-sm-9">
                    {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i:s') : '-' }}
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">
                    {{ __('backend.cart.inc.payment') }} {{ __('backend.cart.amount') }}
                </label>
                <div class="col-sm-9">
                    @if ($isRefunded)
                        <span class="text-decoration-line-through text-muted">
                            {{ sprintf('%s€', number_format($entry->priceSold, 2)) }}
                        </span>
                        <span class="text-success ms-2">
                            <i class="la la-undo"></i> Reembolsado
                        </span>
                    @else
                        {{ sprintf('%s€', number_format($entry->priceSold, 2)) }}
                    @endif
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">
                    {{ __('backend.cart.inc.payment') }} {{ __('backend.cart.platform') }}
                </label>
                <div class="col-sm-9">
                    @php
                        $paymentType = $payment?->getTicketOfficePaymentType();
                    @endphp
                    {{ $payment->gateway }}
                    @if ($payment->tpv_name)
                        ({{ $payment->tpv_name }})
                    @elseif($paymentType)
                        ({{ __('backend.ticket.payment_type.' . $paymentType) }})
                    @endif
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.soldby') }}</label>
                <div class="col-sm-9">
                    @php
                        if ($entry->seller instanceof App\Models\Application) {
                            echo sprintf('External application (%s)', $entry->seller->code_name);
                        } elseif ($entry->seller instanceof App\Models\User) {
                            echo sprintf('%s (%s)', $entry->seller->name, $entry->seller->email);
                        } else {
                            echo '-';
                        }
                    @endphp
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════ --}}
            {{-- GATEWAY RESPONSE (colapsable) --}}
            {{-- ═══════════════════════════════════════════════════════════════ --}}
            @if ($payment->gateway_response)
                <div class="row mb-2">
                    <label class="col-sm-3 fw-semibold">
                        {{ __('backend.cart.inc.gateway_response') ?? 'Gateway Response' }}
                    </label>
                    <div class="col-sm-9">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#gatewayResponse{{ $entry->id }}" aria-expanded="false">
                            <i class="la la-eye me-1"></i> {{ __('backend.cart.inc.show_detail') ?? 'Ver detalle' }}
                        </button>

                        <div class="collapse mt-2" id="gatewayResponse{{ $entry->id }}">
                            <div class="card card-body bg-light">
                                @php
                                    $gatewayData = json_decode($payment->gateway_response, true);
                                @endphp

                                @if ($gatewayData)
                                    <table class="table table-sm table-striped mb-0">
                                        <tbody>
                                            @foreach ($gatewayData as $key => $value)
                                                <tr>
                                                    <td class="fw-semibold text-nowrap" style="width: 40%;">
                                                        {{ str_replace('_', ' ', ucfirst($key)) }}:
                                                    </td>
                                                    <td>
                                                        @if (is_array($value))
                                                            {{ json_encode($value) }}
                                                        @else
                                                            {{ urldecode($value) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <pre class="mb-0 small">{{ $payment->gateway_response }}</pre>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <p class="text-muted mb-0">
                <i class="la la-info-circle me-1"></i>
                {{ __('backend.cart.no_payment') }}
            </p>
        @endif
    </div>
</div>
